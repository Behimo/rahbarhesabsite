<?php

namespace App\Models;

use App\Models\Concerns\HasCategories;
use Database\Factories\AccountingLawFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AccountingLaw extends Model
{
    /** @use HasFactory<AccountingLawFactory> */
    use HasCategories, HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_SCHEDULED = 'scheduled';

    protected $fillable = [
        'article_number',
        'title',
        'slug',
        'introduction',
        'article_text',
        'use_cases',
        'plain_language',
        'example',
        'conclusion',
        'featured_image',
        'featured_image_alt',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_published',
        'status',
        'published_at',
        'views',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'views' => 'integer',
        ];
    }

    public function evolutions(): HasMany
    {
        return $this->hasMany(LawEvolution::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished($query)
    {
        return $query
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', self::STATUS_PUBLISHED);
            });
    }

    public function isLive(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        if ($this->status && $this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        return ! $this->published_at || $this->published_at->lte(now());
    }

    public function getDisplayTitleAttribute(): string
    {
        $number = trim((string) $this->article_number);

        if ($number === '') {
            return (string) $this->title;
        }

        return 'ماده '.$number.' — '.$this->title;
    }

    public function excerpt(int $limit = 160): string
    {
        $text = trim(strip_tags((string) $this->introduction));

        return $text === '' ? '' : Str::limit($text, $limit);
    }
}
