<?php

namespace App\Services\Orders;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderCreator
{
    public function __construct(private CouponService $coupons) {}

    /**
     * @param  Collection<int, CartItem>  $items
     */
    public function create(
        User $user,
        Collection $items,
        ?Coupon $coupon,
        string $channel,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $idempotencyKey = null,
        ?string $requestFingerprint = null,
    ): Order {
        if ($items->isEmpty()) {
            throw new \RuntimeException('سبد خرید خالی است.');
        }

        $reusable = $idempotencyKey === null
            ? $this->reusablePendingOrder($user, $items, $coupon, $channel)
            : null;
        if ($reusable) {
            return $reusable;
        }

        return DB::transaction(function () use ($user, $items, $coupon, $channel, $ip, $userAgent, $idempotencyKey, $requestFingerprint) {
            $lockedProducts = ShopProduct::query()
                ->whereIn('id', $items->pluck('shop_product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $lockedProducts->get($item->shop_product_id);
                if (! $product || ! $product->is_published) {
                    throw new \RuntimeException('یکی از محصولات سبد خرید دیگر در دسترس نیست.');
                }
                if ($product->tracksInventory() && $item->quantity > (int) $product->stock) {
                    throw new \RuntimeException('موجودی محصول «'.$product->title.'» برای تعداد درخواستی کافی نیست.');
                }

                $item->setRelation('product', $product);
            }

            $subtotal = (int) $items->sum(
                fn ($item) => (int) $item->product->effectivePrice() * (int) $item->quantity
            );
            $discount = $coupon ? $this->coupons->discountFor($coupon, $items) : 0;
            $total = max(0, $subtotal - $discount);

            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => Order::STATUS_PENDING,
                'channel' => $channel,
                'idempotency_key' => $idempotencyKey,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'currency' => Money::STORE_CURRENCY,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'user_ip' => $ip,
                'user_agent' => $userAgent,
            ]);

            foreach ($items as $item) {
                $product = $lockedProducts->get($item->shop_product_id);
                $reserved = $this->reserveItem($product, (int) $item->quantity);

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
                        'stock_reserved' => $reserved,
                        'app_request_fingerprint' => $requestFingerprint,
                    ],
                ]);
            }

            return $order->load('items.product');
        });
    }

    /**
     * @param  Collection<int, CartItem>  $items
     */
    private function reusablePendingOrder(User $user, Collection $items, ?Coupon $coupon, string $channel): ?Order
    {
        $pending = Order::query()
            ->where('user_id', $user->id)
            ->where('status', Order::STATUS_PENDING)
            ->where('channel', $channel)
            ->latest('id')
            ->first();

        if (! $pending) {
            return null;
        }

        $pending->load('items');
        $current = $items->map(fn ($item) => $item->shop_product_id.':'.$item->quantity)->sort()->values()->all();
        $existing = $pending->items->map(fn ($item) => $item->shop_product_id.':'.$item->quantity)->sort()->values()->all();

        if ($current !== $existing || ! $this->pendingQuoteMatches($pending, $items, $coupon)) {
            app(OrderService::class)->markFailed($pending);

            return null;
        }

        return $pending->load('items.product');
    }

    /**
     * @param  Collection<int, CartItem>  $items
     */
    private function pendingQuoteMatches(Order $pending, Collection $items, ?Coupon $coupon): bool
    {
        $items->loadMissing('product');

        if ($items->contains(fn ($item) => $item->product === null || ! $item->product->is_published)) {
            return false;
        }

        $pricesMatch = $pending->items->every(function (OrderItem $item) use ($items) {
            $cartItem = $items->first(
                fn ($cartItem) => (int) $cartItem->shop_product_id === (int) $item->shop_product_id
            );

            return $cartItem && (int) $item->price === (int) $cartItem->product->effectivePrice();
        });

        $subtotal = (int) $items->sum(
            fn ($item) => (int) $item->product->effectivePrice() * (int) $item->quantity
        );
        $discount = $coupon ? $this->coupons->discountFor($coupon, $items) : 0;
        $total = max(0, $subtotal - $discount);

        return $pricesMatch
            && (int) $pending->coupon_id === (int) ($coupon?->id ?? 0)
            && (int) $pending->subtotal === $subtotal
            && (int) $pending->discount === $discount
            && (int) $pending->total === $total;
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
}
