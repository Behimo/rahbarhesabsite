<?php

namespace App\Models;

use App\Models\Concerns\HasCategories;
use App\Models\Concerns\HasTags;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CafeQuestion extends Model
{
    /** @var array<string, string> */
    private const STATUS_LABELS = [
        self::STATUS_PENDING => 'در انتظار پاسخ',
        self::STATUS_ANSWERED => 'پاسخ داده‌شده',
        self::STATUS_REJECTED => 'رد شده',
    ];

    use HasCategories, HasTags, SoftDeletes;

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
        'meta_title',
        'meta_description',
        'robots',
        'canonical_url',
        'og_image',
        'featured_image',
        'video_url',
        'video_poster',
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

    public function bodyHtml(): string
    {
        return $this->wp_post_id ? wp_autop_html($this->body) : nl2br(e($this->body));
    }

    public function url(): string
    {
        return app(\App\Services\PermalinkService::class)->questionAbsolute($this);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CafeAnswer::class);
    }

    public function officialAnswer(): HasOne
    {
        return $this->hasOne(CafeAnswer::class)->where('is_official', true);
    }

    public function communityAnswers(): HasMany
    {
        return $this->hasMany(CafeAnswer::class)->where('is_official', false)->whereNull('parent_id');
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
