<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_order_amount' => 'decimal:2',
        'is_first_order_only' => 'boolean',
        'exclude_sale_items' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function targets(): HasMany
    {
        return $this->hasMany(CouponTarget::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValidForCart(float $subtotal, ?User $user = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit_total && $this->used_count >= $this->usage_limit_total) {
            return false;
        }

        if ($this->min_order_amount && $subtotal < (float) $this->min_order_amount) {
            return false;
        }

        if ($this->max_order_amount && $subtotal > (float) $this->max_order_amount) {
            return false;
        }

        if ($user && $this->usage_limit_per_user) {
            $userUsage = $this->usages()->where('user_id', $user->id)->count();
            if ($userUsage >= $this->usage_limit_per_user) {
                return false;
            }

            if ($this->is_first_order_only) {
                $hasPriorOrders = Order::where('user_id', $user->id)
                    ->where('status', Order::STATUS_PAID)
                    ->exists();
                if ($hasPriorOrders) {
                    return false;
                }
            }
        }

        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        $discount = 0.0;

        if (str_starts_with($this->type, 'percentage')) {
            $discount = ($subtotal * (float) $this->value) / 100;
            if ($this->max_discount_amount && $discount > (float) $this->max_discount_amount) {
                $discount = (float) $this->max_discount_amount;
            }
        } else {
            $discount = (float) $this->value;
        }

        return min($discount, $subtotal);
    }
}
