<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'order_id', 'gateway', 'authority', 'ref_id', 'amount', 'status',
        'card_pan', 'card_hash', 'gateway_payload', 'gateway_response', 'verified_at', 'paid_at', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'gateway_payload' => 'array',
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
