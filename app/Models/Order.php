<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal', 'discount',
        'total', 'currency', 'notes', 'paid_at', 'wp_id', 'coupon_id',
        'coupon_code', 'user_ip', 'user_agent',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The payment that actually settled a paid order, otherwise the latest attempt.
     */
    public function receiptPayment(): ?Payment
    {
        $payments = $this->relationLoaded('payments')
            ? $this->payments
            : $this->payments()->get();

        $successful = $payments
            ->where('status', Payment::STATUS_SUCCESS)
            ->sortByDesc('id')
            ->first();

        return $successful ?? $payments->sortByDesc('id')->first();
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function canRetryPayment(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_FAILED], true) && $this->total > 0;
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-'.Str::ulid();
    }
}
