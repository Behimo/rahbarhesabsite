<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPostPaymentTask extends Model
{
    public const TYPE_COUPON = 'coupon';

    public const TYPE_CART_CLEAR = 'cart_clear';

    public const TYPE_LICENSE = 'license';

    protected $fillable = [
        'order_id', 'task_key', 'type', 'payload', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'completed_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'rejected_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
