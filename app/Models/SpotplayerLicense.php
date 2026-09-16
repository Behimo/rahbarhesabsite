<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotplayerLicense extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
            'user_id', 'course_id', 'order_id', 'spot_license_id', 'license_key',
            'spot_url', 'device_count', 'devices_limit',
            'status', 'issued_via', 'last_verified_at', 'api_response', 'issued_at',
        ];

        protected function casts(): array
        {
            return [
                'api_response' => 'array',
                'issued_at' => 'datetime',
                'last_verified_at' => 'datetime',
                'device_count' => 'integer',
                'devices_limit' => 'integer',
            ];
        }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
