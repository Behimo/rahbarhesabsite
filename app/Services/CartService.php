<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\ShopProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CartService
{
    public function items(): Collection
    {
        $query = CartItem::query()->with('product');

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        } else {
            $query->where('session_id', session()->getId());
        }

        return $query->get();
    }

    public function count(): int
    {
        return $this->items()->sum('quantity');
    }

    public function subtotal(): int
    {
        return $this->items()->sum(fn (CartItem $item) => $item->product->effectivePrice() * $item->quantity);
    }

    public function add(ShopProduct $product, int $quantity = 1): void
    {
        $attributes = Auth::check()
            ? ['user_id' => Auth::id(), 'shop_product_id' => $product->id]
            : ['session_id' => session()->getId(), 'shop_product_id' => $product->id];

        $item = CartItem::query()->firstOrNew($attributes);

        if (! $product->tracksInventory()) {
            $item->quantity = 1;
            $item->save();

            return;
        }

        $next = ($item->exists ? $item->quantity : 0) + $quantity;
        $this->assertInStock($product, $next);
        $item->quantity = $next;
        $item->save();
    }

    public function update(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $product = $item->product;

        if (! $product || ! $product->tracksInventory()) {
            $item->update(['quantity' => 1]);

            return;
        }

        $this->assertInStock($product, $quantity);
        $item->update(['quantity' => $quantity]);
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(): void
    {
        if (Auth::check()) {
            $this->clearForUser((int) Auth::id());
        } else {
            CartItem::query()->where('session_id', session()->getId())->delete();
        }
    }

    public function clearForUser(int $userId): void
    {
        CartItem::query()->where('user_id', $userId)->delete();
        app(CouponService::class)->forget();
    }

    private function assertInStock(ShopProduct $product, int $quantity): void
    {
        if ($product->tracksInventory() && $quantity > (int) $product->stock) {
            throw new \RuntimeException('موجودی محصول «'.$product->title.'» برای تعداد درخواستی کافی نیست.');
        }
    }

    public function mergeGuestCart(int $userId): void
    {
        $sessionId = session()->getId();

        CartItem::query()
            ->where('session_id', $sessionId)
            ->each(function (CartItem $guestItem) use ($userId) {
                $existing = CartItem::query()
                    ->where('user_id', $userId)
                    ->where('shop_product_id', $guestItem->shop_product_id)
                    ->first();

                if ($existing) {
                    $product = $existing->product ?? ShopProduct::query()->find($existing->shop_product_id);
                    $quantity = $existing->quantity + $guestItem->quantity;

                    if (! $product || ! $product->tracksInventory()) {
                        $quantity = 1;
                    } else {
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
    }
}
