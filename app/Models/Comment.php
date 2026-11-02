<?php

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SPAM = 'spam';

    public const STATUS_TRASH = 'trash';

    public const ITEM_STATUS_INHERIT = 'inherit';

    public const ITEM_STATUS_OPEN = 'open';

    public const ITEM_STATUS_CLOSED = 'closed';

    public const MODERATION_INHERIT = 'inherit';

    public const MODERATION_MANUAL = 'manual';

    public const MODERATION_AUTO = 'auto';

    protected $fillable = [
        'user_id',
        'author_name',
        'author_email',
        'author_ip',
        'user_agent',
        'commentable_type',
        'commentable_id',
        'parent_id',
        'body',
        'status',
        'is_pinned',
        'is_admin_reply',
        'spam_score',
        'spam_reasons',
        'approved_at',
        'approved_by',
        'edited_at',
        'wp_comment_id',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_admin_reply' => 'boolean',
            'spam_reasons' => 'array',
            'approved_at' => 'datetime',
            'edited_at' => 'datetime',
        ];
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeVisibleOnSite(Builder $query): Builder
    {
        return $query->approved();
    }

    public function displayName(): string
    {
        if ($this->user) {
            return (string) ($this->user->name ?: 'کاربر');
        }

        return (string) ($this->author_name ?: 'مهمان');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'در انتظار',
            self::STATUS_APPROVED => 'تأیید شده',
            self::STATUS_REJECTED => 'رد شده',
            self::STATUS_SPAM => 'اسپم',
            self::STATUS_TRASH => 'زباله‌دان',
        ];
    }
}
