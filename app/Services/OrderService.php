<?php

namespace App\Services;

use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private CartService $cart,
        private CouponService $coupons,
    ) {}

    public function createFromCart(User $user, ?string $ip = null, ?string $userAgent = null): Order
    {
        $items = $this->cart->items();

        if ($items->isEmpty()) {
            throw new \RuntimeException('سبد خرید خالی است.');
        }

        $coupon = $this->coupons->applied($user);
        $subtotal = $this->cart->subtotal();
        $discount = $coupon ? $this->coupons->discountFor($coupon, $items) : 0;
        $total = max(0, $subtotal - $discount);

        return DB::transaction(function () use ($user, $items, $coupon, $subtotal, $discount, $total, $ip, $userAgent) {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => Order::STATUS_PENDING,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'user_ip' => $ip,
                'user_agent' => $userAgent,
            ]);

            foreach ($items as $item) {
                $product = $item->product;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'shop_product_id' => $item->shop_product_id,
                    'title' => $product->title,
                    'price' => $product->effectivePrice(),
                    'quantity' => $item->quantity,
                    'metadata' => [
                        'slug' => $product->slug,
                        'type' => $product->type,
                        'course_ids' => $product->relatedCourses()->pluck('id')->all(),
                    ],
                ]);
            }

            return $order->load('items.product');
        });
    }

    public function markPaid(Order $order, array $paymentFields = []): void
    {
        $order->refresh();

        if ($order->isPaid()) {
            return;
        }

        DB::transaction(function () use ($order, $paymentFields) {
            $locked = Order::query()->lockForUpdate()->find($order->id);

            if (! $locked || $locked->isPaid()) {
                return;
            }

            $locked->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
            ]);

            if ($locked->coupon_id) {
                $this->coupons->recordUsage($locked);
            }

            $this->fulfill($locked->fresh(['items.product.course', 'user']));
        });

        $this->cart->clearForUser((int) $order->user_id);

        if ($paymentFields !== []) {
            $order->payment()?->update($paymentFields);
        }
    }

    public function fulfill(Order $order): void
    {
        if (! $order->isPaid()) {
            return;
        }

        foreach ($order->items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            foreach ($product->relatedCourses() as $course) {
                $this->enrollUser($order->user, $course, $order, CourseEnrollment::SOURCE_PURCHASE);
            }
        }
    }

    public function enrollUser(
        User $user,
        Course $course,
        ?Order $order = null,
        string $source = CourseEnrollment::SOURCE_PURCHASE,
    ): CourseEnrollment {
        $months = (int) config('cms.enrollment_months', 12);

        $enrollment = CourseEnrollment::query()->firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'order_id' => $order?->id,
                'enrolled_at' => now(),
                'expires_at' => $months > 0 ? now()->addMonths($months) : null,
                'status' => CourseEnrollment::STATUS_ACTIVE,
                'source' => $source,
            ]
        );

        if ($enrollment->status !== CourseEnrollment::STATUS_ACTIVE) {
            $enrollment->update([
                'status' => CourseEnrollment::STATUS_ACTIVE,
                'order_id' => $order?->id ?? $enrollment->order_id,
                'expires_at' => $months > 0 ? now()->addMonths($months) : null,
            ]);
        }

        if ($course->spotplayer_course_id) {
            IssueSpotplayerLicenseJob::dispatch($user->id, $course->id, $order?->id);
        }

        return $enrollment;
    }

    public function revokeEnrollment(CourseEnrollment $enrollment): void
    {
        $enrollment->update([
            'status' => CourseEnrollment::STATUS_REVOKED,
            'expires_at' => now(),
        ]);
    }

    public function retryLicenses(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            foreach ($product->relatedCourses() as $course) {
                if ($course->spotplayer_course_id) {
                    IssueSpotplayerLicenseJob::dispatch($order->user_id, $course->id, $order->id);
                }
            }
        }
    }

    public function updateCourseProgress(User $user, Course $course): void
    {
        $lessonIds = CourseLesson::query()
            ->whereIn('section_id', $course->sections()->pluck('id'))
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return;
        }

        $completed = LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->count();

        $percent = (int) round(($completed / $lessonIds->count()) * 100);

        $enrollment = CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (! $enrollment) {
            return;
        }

        $enrollment->update([
            'progress_percent' => $percent,
            'completed_at' => $percent >= 100 ? now() : null,
        ]);
    }
}
