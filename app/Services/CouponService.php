<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Collection;

class CouponService
{
    public function findActive(string $code): ?Coupon
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        return Coupon::query()->where('code', $code)->first();
    }

    public function applied(?User $user = null): ?Coupon
    {
        $code = (string) session('cart.coupon_code', '');

        if ($code === '') {
            return null;
        }

        $coupon = $this->findActive($code);

        if (! $coupon) {
            session()->forget('cart.coupon_code');

            return null;
        }

        $items = app(CartService::class)->items();

        try {
            $this->assertValid($coupon, $items, $user ?? auth()->user(), app(CartService::class)->subtotal());
        } catch (\RuntimeException) {
            session()->forget('cart.coupon_code');

            return null;
        }

        return $coupon;
    }

    public function apply(string $code, Collection $items, float $subtotal, ?User $user = null): Coupon
    {
        $coupon = $this->findActive($code);

        if (! $coupon) {
            throw new \RuntimeException('کد تخفیف معتبر نیست.');
        }

        $this->assertValid($coupon, $items, $user, $subtotal);
        session(['cart.coupon_code' => $coupon->code]);

        return $coupon;
    }

    public function forget(): void
    {
        session()->forget('cart.coupon_code');
    }

    public function assertValid(Coupon $coupon, Collection $items, ?User $user, float $subtotal): void
    {
        if (! $coupon->isValidForCart($subtotal, $user)) {
            throw new \RuntimeException('این کد تخفیف قابل استفاده نیست.');
        }

        $eligible = $this->eligibleSubtotal($coupon, $items);

        if ($eligible <= 0) {
            throw new \RuntimeException('این کد روی اقلام سبد اعمال نمی‌شود.');
        }
    }

    public function eligibleSubtotal(Coupon $coupon, Collection $items): float
    {
        $included = $coupon->targets()->where('is_excluded', false)->get();
        $excluded = $coupon->targets()->where('is_excluded', true)->get();

        $total = 0.0;

        foreach ($items as $item) {
            /** @var CartItem $item */
            $product = $item->product;

            if (! $product) {
                continue;
            }

            if ($coupon->exclude_sale_items && $product->sale_price) {
                continue;
            }

            if ($this->matchesTargets($excluded, $product, $item)) {
                continue;
            }

            if ($included->isNotEmpty() && ! $this->matchesTargets($included, $product, $item)) {
                continue;
            }

            $total += $product->effectivePrice() * $item->quantity;
        }

        return $total;
    }

    public function discountFor(Coupon $coupon, Collection $items): int
    {
        $eligible = $this->eligibleSubtotal($coupon, $items);

        return (int) round($coupon->calculateDiscount($eligible));
    }

    public function recordUsage(Order $order): void
    {
        if (! $order->coupon_id || ! $order->user_id) {
            return;
        }

        CouponUsage::query()->firstOrCreate(
            ['coupon_id' => $order->coupon_id, 'order_id' => $order->id],
            [
                'user_id' => $order->user_id,
                'discount_amount' => $order->discount,
                'used_at' => now(),
            ]
        );

        Coupon::query()->whereKey($order->coupon_id)->increment('used_count');
    }

    private function matchesTargets(Collection $targets, ShopProduct $product, CartItem $item): bool
    {
        foreach ($targets as $target) {
            if ($target->target_type === 'product' && (int) $target->target_id === (int) $product->id) {
                return true;
            }

            if ($target->target_type === 'user' && $item->user_id && (int) $target->target_id === (int) $item->user_id) {
                return true;
            }

            if ($target->target_type === 'category') {
                $termIds = $product->taxonomyTerms()->pluck('cms_taxonomy_terms.id')->all();
                if (in_array((int) $target->target_id, array_map('intval', $termIds), true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
