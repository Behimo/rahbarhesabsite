<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CafeAnswer extends Model
{
    protected $fillable = [
        'cafe_question_id',
        'user_id',
        'body',
        'is_official',
        'parent_id',
        'author_name',
    ];

    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
        ];
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function authorDisplayName(): string
    {
        return $this->user?->displayName() ?? $this->author_name ?? 'کاربر';
    }

    public function bodyHtml(): string
    {
        if ($this->is_official) {
            return wp_autop_html($this->body);
        }

        return $this->wp_comment_id ? wp_autop_html($this->body) : nl2br(e($this->body));
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CafeQuestion::class, 'cafe_question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
