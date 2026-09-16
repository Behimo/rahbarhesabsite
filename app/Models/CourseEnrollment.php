<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEnrollment extends Model
{
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
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
