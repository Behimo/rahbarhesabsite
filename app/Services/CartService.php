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
        $item->quantity = ($item->exists ? $item->quantity : 0) + $quantity;
        $item->save();
    }

    public function update(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => $quantity]);
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(): void
    {
        if (Auth::check()) {
            CartItem::query()->where('user_id', Auth::id())->delete();
        } else {
            CartItem::query()->where('session_id', session()->getId())->delete();
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
                    $existing->update(['quantity' => $existing->quantity + $guestItem->quantity]);
                    $guestItem->delete();
                } else {
                    $guestItem->update(['user_id' => $userId, 'session_id' => null]);
                }
            });
    }
}
