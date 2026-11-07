<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPostPaymentTask;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\Orders\OrderCreator;
use App\Services\Orders\PostPaymentProcessor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private CourseEnrollmentService $enrollments,
        private CourseProgressService $progress,
        private OrderCreator $orderCreator,
        private PostPaymentProcessor $postPayment,
    ) {}

    /**
     * @param  Collection<int, CartItem>  $items
     */
    public function createFromCart(User $user, Collection $items, ?Coupon $coupon = null, ?string $ip = null, ?string $userAgent = null): Order
    {
        if ($items->isEmpty()) {
            throw new \RuntimeException('سبد خرید خالی است.');
        }

        return $this->orderCreator->create(
            $user,
            $items,
            $coupon,
            Order::CHANNEL_WEB,
            $ip,
            $userAgent,
        );
    }

    public function markPaid(Order $order, array $paymentFields = [], ?Payment $payment = null): void
    {
        $order->refresh();

        if (! $order->isPaid()) {
            DB::transaction(function () use ($order, $paymentFields, $payment) {
                $locked = Order::query()->lockForUpdate()->find($order->id);

                if (! $locked || $locked->isPaid()) {
                    return;
                }

                $locked->load('items.product');
                foreach ($locked->items as $item) {
                    if ($item->metadata['stock_reserved'] ?? false) {
                        continue;
                    }

                    $product = $item->product;
                    if (! $product?->tracksInventory()) {
                        continue;
                    }

                    $this->reserveItem($product, (int) $item->quantity);
                }

                $locked->update([
                    'status' => Order::STATUS_PAID,
                    'paid_at' => now(),
                ]);

                if ($paymentFields !== []) {
                    $this->recordPaymentResult($locked, $paymentFields, $payment);
                }

                $paid = $locked->fresh(['items', 'user']);
                $this->fulfill($paid);
                $this->queuePostPaymentTasks($paid);
            });
        }

        $fresh = $order->fresh();
        if ($fresh?->isPaid()) {
            $this->postPayment->process($fresh);
        }
    }

    public function markFailed(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->failLockedOrder(Order::query()->lockForUpdate()->find($order->id));
        });
    }

    /**
     * Fail the order only when no other payment attempt is still open.
     * A stale cancel for an older attempt must not release stock or
     * abandon the payment the customer is completing now.
     */
    public function markFailedFromPayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $locked = Order::query()->lockForUpdate()->find($payment->order_id);

            if (! $locked || $locked->isPaid()) {
                return;
            }

            $hasOtherOpenPayment = Payment::query()
                ->where('order_id', $locked->id)
                ->whereKeyNot($payment->id)
                ->where('status', Payment::STATUS_PENDING)
                ->exists();

            if ($hasOtherOpenPayment) {
                return;
            }

            $this->failLockedOrder($locked);
        });
    }

    public function reserveForRetry(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->load('items');
            $productIds = $order->items->pluck('shop_product_id')->filter()->unique()->sort()->values();
            $lockedProducts = ShopProduct::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($order->items as $item) {
                if ($item->metadata['stock_reserved'] ?? false) {
                    continue;
                }

                $product = $lockedProducts->get($item->shop_product_id);

                if (! $product || ! $product->is_published) {
                    throw new \RuntimeException('یکی از محصولات سفارش دیگر در دسترس نیست.');
                }

                if (! $this->reserveItem($product, (int) $item->quantity)) {
                    continue;
                }

                $metadata = $item->metadata ?? [];
                $metadata['stock_reserved'] = true;
                $item->update(['metadata' => $metadata]);
            }
        });
    }

    public function fulfill(Order $order): void
    {
        if (! $order->isPaid() || ! $order->user) {
            return;
        }

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            foreach ($item->purchasedCourses() as $course) {
                $this->enrollments->enroll($order->user, $course, $order, CourseEnrollment::SOURCE_PURCHASE, false);
            }
        }
    }

    public function enrollUser(
        User $user,
        Course $course,
        ?Order $order = null,
        string $source = CourseEnrollment::SOURCE_PURCHASE,
    ): CourseEnrollment {
        return $this->enrollments->enroll($user, $course, $order, $source);
    }

    public function revokeEnrollment(CourseEnrollment $enrollment): void
    {
        $this->enrollments->revoke($enrollment);
    }

    public function retryLicenses(Order $order): void
    {
        $this->enrollments->retryLicenses($order);
    }

    public function updateCourseProgress(User $user, Course $course): void
    {
        $this->progress->update($user, $course);
    }

    private function queuePostPaymentTasks(Order $order): void
    {
        $order->loadMissing('items');

        if ($order->coupon_id) {
            $this->firstPostPaymentTask($order, 'coupon', OrderPostPaymentTask::TYPE_COUPON, [
                'coupon_id' => (int) $order->coupon_id,
            ]);
        }

        $productIds = $order->items
            ->pluck('shop_product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->firstPostPaymentTask($order, 'cart_clear', OrderPostPaymentTask::TYPE_CART_CLEAR, [
            'product_ids' => $productIds,
        ]);

        if (! $order->user_id) {
            return;
        }

        foreach ($order->items as $item) {
            foreach ($item->purchasedCourses() as $course) {
                if (! $course->spotplayer_course_id) {
                    continue;
                }

                $this->firstPostPaymentTask($order, 'license:'.$course->id, OrderPostPaymentTask::TYPE_LICENSE, [
                    'user_id' => (int) $order->user_id,
                    'course_id' => (int) $course->id,
                ]);
            }
        }
    }

    /** @param  array<string, mixed>  $payload */
    private function firstPostPaymentTask(Order $order, string $key, string $type, array $payload): void
    {
        OrderPostPaymentTask::query()->firstOrCreate(
            ['order_id' => $order->id, 'task_key' => $key],
            ['type' => $type, 'payload' => $payload],
        );
    }

    /** @param  array<string, mixed>  $paymentFields */
    private function recordPaymentResult(Order $order, array $paymentFields, ?Payment $payment): void
    {
        $target = Payment::query()
            ->where('order_id', $order->id)
            ->when($payment, fn ($query) => $query->whereKey($payment->id))
            ->when(! $payment, fn ($query) => $query->latest('id'))
            ->lockForUpdate()
            ->first();

        $target?->update($paymentFields);
    }

    private function failLockedOrder(?Order $locked): void
    {
        if (! $locked || $locked->isPaid()) {
            return;
        }

        $this->releaseReservedStock($locked);

        if ($locked->status !== Order::STATUS_FAILED) {
            $locked->update(['status' => Order::STATUS_FAILED]);
        }

        Payment::query()
            ->where('order_id', $locked->id)
            ->where('status', Payment::STATUS_PENDING)
            ->update([
                'status' => Payment::STATUS_FAILED,
                'error_message' => 'سفارش بسته شد.',
            ]);
    }

    private function reserveItem(ShopProduct $product, int $quantity): bool
    {
        if (! $product->tracksInventory()) {
            return false;
        }

        $updated = ShopProduct::query()
            ->whereKey($product->id)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        if ($updated !== 1) {
            throw new \RuntimeException('موجودی محصول «'.$product->title.'» برای تعداد درخواستی کافی نیست.');
        }

        $product->stock = (int) $product->stock - $quantity;

        return true;
    }

    private function releaseReservedStock(Order $order): void
    {
        $order->load('items');
        $reserved = $order->items->filter(
            fn (OrderItem $item) => (bool) ($item->metadata['stock_reserved'] ?? false)
        );

        if ($reserved->isEmpty()) {
            return;
        }

        ShopProduct::query()
            ->whereIn('id', $reserved->pluck('shop_product_id')->filter()->unique())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($reserved as $item) {
            if ($item->shop_product_id) {
                ShopProduct::query()->whereKey($item->shop_product_id)->increment('stock', $item->quantity);
            }

            $metadata = $item->metadata ?? [];
            $metadata['stock_reserved'] = false;
            $item->update(['metadata' => $metadata]);
        }
    }
}
