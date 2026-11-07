<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\ShopProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function items(): Collection
    {
        return $this->remembered($this->ownerKey(), function () {
            $query = CartItem::query()->with('product');

            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            } else {
                $query->where('session_id', session()->getId());
            }

            return $query->get();
        });
    }

    public function itemsForUser(int $userId): Collection
    {
        return $this->remembered('user:'.$userId, function () use ($userId) {
            return CartItem::query()->with('product')->where('user_id', $userId)->get();
        });
    }

    public function count(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    public function quantityTotal(): int
    {
        $query = CartItem::query();

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        } else {
            $query->where('session_id', session()->getId());
        }

        return (int) $query->sum('quantity');
    }

    public function flushMemo(): void
    {
        app()->forgetInstance('cart.items.memo');
    }

    public function subtotal(): int
    {
        return $this->items()->sum(fn (CartItem $item) => $item->product->effectivePrice() * $item->quantity);
    }

    public function add(ShopProduct $product, int $quantity = 1): void
    {
        $this->flushMemo();

        DB::transaction(function () use ($product, $quantity) {
            $item = $this->lockedOwnerItem($product->id);

            if (! $item) {
                $item = new CartItem(Auth::check()
                    ? ['user_id' => Auth::id(), 'shop_product_id' => $product->id]
                    : ['session_id' => session()->getId(), 'shop_product_id' => $product->id]);
            }

            if ($this->forcesSingleUnit($product)) {
                $item->quantity = 1;
                $item->save();

                return;
            }

            $next = ($item->exists ? $item->quantity : 0) + $quantity;
            $this->assertInStock($product, $next);
            $item->quantity = $next;
            $item->save();
        });
    }

    public function update(CartItem $item, int $quantity): void
    {
        $this->flushMemo();

        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $product = $item->product;

        if (! $product) {
            $item->delete();

            return;
        }

        if ($this->forcesSingleUnit($product)) {
            $item->update(['quantity' => 1]);

            return;
        }

        $this->assertInStock($product, $quantity);
        $item->update(['quantity' => $quantity]);
    }

    public function remove(CartItem $item): void
    {
        $this->flushMemo();
        $item->delete();
    }

    public function clear(): void
    {
        $this->flushMemo();

        if (Auth::check()) {
            $this->clearForUser((int) Auth::id());
        } else {
            CartItem::query()->where('session_id', session()->getId())->delete();
        }
    }

    public function clearForUser(int $userId): void
    {
        $this->flushMemo();
        CartItem::query()->where('user_id', $userId)->delete();
        app(CouponService::class)->forget();
    }

    /**
     * @param  array<int, int>  $productIds
     */
    public function clearProductsForUser(int $userId, array $productIds): void
    {
        $this->flushMemo();

        if ($productIds === []) {
            return;
        }

        CartItem::query()
            ->where('user_id', $userId)
            ->whereIn('shop_product_id', $productIds)
            ->delete();

        app(CouponService::class)->forget();
    }

    private function assertInStock(ShopProduct $product, int $quantity): void
    {
        if ($product->tracksInventory() && $quantity > (int) $product->stock) {
            throw new \RuntimeException('موجودی محصول «'.$product->title.'» برای تعداد درخواستی کافی نیست.');
        }
    }

    /** Courses, digital goods and bundles are sold as a single seat/license. */
    private function forcesSingleUnit(ShopProduct $product): bool
    {
        return in_array($product->type, [
            ShopProduct::TYPE_COURSE,
            ShopProduct::TYPE_DIGITAL,
            ShopProduct::TYPE_BUNDLE,
        ], true);
    }

    public function mergeGuestCart(int $userId, ?string $sessionId = null): void
    {
        $this->flushMemo();
        $sessionId = $sessionId ?: session()->getId();

        DB::transaction(function () use ($userId, $sessionId) {
            CartItem::query()
                ->where('session_id', $sessionId)
                ->whereNull('user_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->each(function (CartItem $guestItem) use ($userId) {
                    $existing = CartItem::query()
                        ->where('user_id', $userId)
                        ->where('shop_product_id', $guestItem->shop_product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($existing) {
                        $product = $existing->product ?? ShopProduct::query()->find($existing->shop_product_id);
                        $quantity = $existing->quantity + $guestItem->quantity;

                        if (! $product || $this->forcesSingleUnit($product)) {
                            $quantity = 1;
                        } elseif ($product->tracksInventory()) {
                            $quantity = min($quantity, (int) $product->stock);
                        }

                        if ($quantity < 1) {
                            $existing->delete();
                            $guestItem->delete();

                            return;
                        }

                        $existing->update(['quantity' => $quantity]);
                        $guestItem->delete();
                    } else {
                        $guestItem->update(['user_id' => $userId, 'session_id' => null]);
                    }
                });
        });
    }

    private function lockedOwnerItem(int $productId): ?CartItem
    {
        $query = CartItem::query()->where('shop_product_id', $productId);

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        } else {
            $query->where('session_id', session()->getId())->whereNull('user_id');
        }

        return $query->lockForUpdate()->first();
    }

    private function ownerKey(): string
    {
        return Auth::check()
            ? 'user:'.Auth::id()
            : 'session:'.session()->getId();
    }

    private function remembered(string $key, callable $load): Collection
    {
        $all = app()->bound('cart.items.memo') ? app('cart.items.memo') : [];

        if (! array_key_exists($key, $all)) {
            $all[$key] = $load();
            app()->instance('cart.items.memo', $all);
        }

        return $all[$key];
    }
}
