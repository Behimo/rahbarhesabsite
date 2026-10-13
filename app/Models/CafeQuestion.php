<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CafeQuestion extends Model
{
    /** @var array<string, string> */
    private const STATUS_LABELS = [
        self::STATUS_PENDING => 'در انتظار پاسخ',
        self::STATUS_ANSWERED => 'پاسخ داده‌شده',
        self::STATUS_REJECTED => 'رد شده',
    ];

    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'body',
        'status',
        'rejection_reason',
        'answered_at',
        'rejected_at',
        'views_count',
        'answers_count',
    ];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
            'rejected_at' => 'datetime',
            'views_count' => 'integer',
            'answers_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CafeAnswer::class);
    }

    public function officialAnswer(): HasMany
    {
        return $this->answers()->where('is_official', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ANSWERED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAnswered(): bool
    {
        return $this->status === self::STATUS_ANSWERED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isPublished(): bool
    {
        return $this->isAnswered();
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return self::STATUS_LABELS;
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}
