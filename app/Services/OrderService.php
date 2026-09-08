<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use App\Support\Hook;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private CartService $cart) {}

    public function createFromCart(User $user): Order
    {
        $items = $this->cart->items();

        if ($items->isEmpty()) {
            throw new \RuntimeException('سبد خرید خالی است.');
        }

        return DB::transaction(function () use ($user, $items) {
            $subtotal = $this->cart->subtotal();

            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => Order::STATUS_PENDING,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
            ]);

            foreach ($items as $item) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'shop_product_id' => $item->shop_product_id,
                    'title' => $item->product->title,
                    'price' => $item->product->effectivePrice(),
                    'quantity' => $item->quantity,
                ]);
            }

            Hook::doAction('order.created', $order);

            return $order->load('items.product');
        });
    }

    public function fulfill(Order $order): void
    {
        if (! $order->isPaid()) {
            return;
        }

        foreach ($order->items as $item) {
            $product = $item->product;

            if ($product->isCourse() && $product->course) {
                $this->enrollUser($order->user, $product->course, $order);
            }

            Hook::doAction('order.item.fulfilled', $order, $item);
        }

        Hook::doAction('order.fulfilled', $order);
    }

    public function enrollUser(User $user, Course $course, ?Order $order = null): CourseEnrollment
    {
        return CourseEnrollment::query()->firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'order_id' => $order?->id,
                'enrolled_at' => now(),
                'expires_at' => now()->addMonths(6),
            ]
        );
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
