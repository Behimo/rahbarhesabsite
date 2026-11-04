<?php

namespace App\Actions\Api\V1\Checkout;

use App\Data\Checkout\CheckoutResult;
use App\Exceptions\Api\ApiException;
use App\Models\CartItem;
use App\Models\Course;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\CouponService;
use App\Services\Orders\OrderCreator;
use App\Services\OrderService;
use App\Services\PaymentGatewayManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

class StartCheckoutAction
{
    public function __construct(
        private OrderCreator $orders,
        private OrderService $orderService,
        private CouponService $coupons,
        private PaymentGatewayManager $payments,
    ) {}

    public function handle(
        User $user,
        int $productId,
        ?string $couponCode,
        ?string $gateway,
        string $idempotencyKey,
        ?string $ip,
        ?string $userAgent,
    ): CheckoutResult {
        $fingerprint = hash('sha256', json_encode([
            'product_id' => $productId,
            'coupon_code' => $couponCode ?: null,
            'gateway' => $gateway ?: null,
        ], JSON_THROW_ON_ERROR));

        $existing = Order::query()
            ->where('user_id', $user->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $this->replay($existing, $productId, $gateway, $couponCode, $fingerprint);
        }

        $product = $this->purchasableProduct($user, $productId);
        $line = new CartItem([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'quantity' => 1,
        ]);
        $line->setRelation('product', $product);
        $items = new Collection([$line]);

        $coupon = null;
        if ($couponCode) {
            try {
                $coupon = $this->coupons->resolveForUser($couponCode, $user, $items);
            } catch (\RuntimeException $exception) {
                throw new ApiException($exception->getMessage(), 'coupon_invalid', 422);
            }
        }

        $gatewayName = $product->effectivePrice() > 0 ? $this->gatewayName($gateway) : null;

        try {
            $order = $this->orders->create(
                $user,
                $items,
                $coupon,
                Order::CHANNEL_APP,
                $ip,
                $userAgent,
                $idempotencyKey,
                $fingerprint,
            );
        } catch (UniqueConstraintViolationException) {
            $replayed = Order::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($replayed) {
                return $this->replay($replayed, $productId, $gateway, $couponCode, $fingerprint);
            }

            throw new ApiException('ثبت سفارش ناموفق بود. دوباره تلاش کنید.', 'checkout_rejected', 422);
        } catch (\RuntimeException $exception) {
            throw new ApiException($exception->getMessage(), 'checkout_rejected', 422);
        }

        return $this->present($order, $gatewayName, true);
    }

    private function purchasableProduct(User $user, int $productId): ShopProduct
    {
        $product = ShopProduct::query()->find($productId);

        if (! $product || ! $product->is_published || ! in_array($product->type, [ShopProduct::TYPE_COURSE, ShopProduct::TYPE_BUNDLE], true)) {
            throw new ApiException('این محصول قابل خرید نیست.', 'product_not_purchasable', 422);
        }

        $courses = $product->relatedCourses();
        if ($courses->isEmpty()) {
            throw new ApiException('این محصول قابل خرید نیست.', 'product_not_purchasable', 422);
        }

        if ($courses->every(fn (Course $course) => $user->isEnrolledIn($course))) {
            throw new ApiException('این دوره را قبلاً تهیه کرده‌اید.', 'course_already_owned', 409);
        }

        return $product;
    }

    private function replay(Order $order, int $productId, ?string $gateway, ?string $couponCode, string $fingerprint): CheckoutResult
    {
        $order->load('items.product');
        $matches = $order->items->contains(
            fn ($item) => (int) $item->shop_product_id === $productId
        );

        $storedFingerprint = $order->items->first()?->metadata['app_request_fingerprint'] ?? null;
        // Orders created before request fingerprints existed are checked against their saved quote.
        $legacyMatches = ($order->coupon_code ?: null) === ($couponCode ?: null)
            && ($gateway === null || ! $order->payments()->where('gateway', '!=', $gateway)->exists());
        if (! $matches || ($storedFingerprint !== null
            ? ! hash_equals($storedFingerprint, $fingerprint)
            : ! $legacyMatches)) {
            throw new ApiException('این کلید تکرار برای سفارش دیگری استفاده شده است.', 'idempotency_conflict', 409);
        }

        if ($order->isPaid()) {
            return new CheckoutResult($order, null);
        }

        return $this->present($order, (int) $order->total > 0 ? $this->gatewayName($gateway) : null, false);
    }

    private function present(Order $order, ?string $gateway, bool $newlyCreated): CheckoutResult
    {
        if ((int) $order->total <= 0) {
            if (! $order->isPaid()) {
                try {
                    $this->orderService->markPaid($order);
                } catch (\RuntimeException $exception) {
                    $this->orderService->markFailed($order);

                    throw new ApiException($exception->getMessage(), 'checkout_rejected', 422);
                } catch (\Throwable) {
                    $this->orderService->markFailed($order);

                    throw new ApiException('ثبت سفارش رایگان ناموفق بود.', 'checkout_rejected', 422);
                }
            }

            return new CheckoutResult($order->fresh(['items.product']), null, $newlyCreated);
        }

        $pending = $order->payments()->where('status', Payment::STATUS_PENDING)->latest('id')->first();
        $startUrl = $pending?->gateway_payload['start_url'] ?? null;
        if (is_string($startUrl) && $startUrl !== '') {
            return new CheckoutResult($order->fresh(['items.product']), $startUrl, $newlyCreated);
        }

        try {
            $paymentUrl = $this->payments->requestPayment($order, (string) $gateway);
        } catch (\RuntimeException $exception) {
            if (! $order->payments()->where('status', Payment::STATUS_PENDING)->exists()) {
                $this->orderService->markFailed($order);
            }

            throw new ApiException($exception->getMessage(), 'payment_failed', 422);
        } catch (\Throwable) {
            throw new ApiException('شروع پرداخت ناموفق بود.', 'payment_failed', 422);
        }

        return new CheckoutResult($order->fresh(['items.product']), $paymentUrl, $newlyCreated);
    }

    private function gatewayName(?string $gateway): string
    {
        try {
            return $this->payments->resolveForCheckout($gateway);
        } catch (\RuntimeException $exception) {
            throw new ApiException($exception->getMessage(), 'payment_failed', 422);
        }
    }
}
