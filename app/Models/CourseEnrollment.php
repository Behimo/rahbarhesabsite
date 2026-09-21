<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEnrollment extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    public const SOURCE_PURCHASE = 'purchase';

    public const SOURCE_FREE = 'free';

    public const SOURCE_GIFT = 'gift';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'user_id', 'course_id', 'order_id', 'enrolled_at',
        'status', 'source', 'progress_percent', 'completed_at', 'expires_at',
    ];

        protected function casts(): array
        {
            return [
                'enrolled_at' => 'datetime',
                'completed_at' => 'datetime',
                'expires_at' => 'datetime',
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

    public function isActive(): bool
    {
        if ($this->status === self::STATUS_REVOKED || $this->status === self::STATUS_EXPIRED) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
