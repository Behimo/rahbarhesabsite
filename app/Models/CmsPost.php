<?php

namespace App\Models;

use App\Models\Concerns\HasCategories;
use App\Models\Concerns\HasTags;
use Database\Factories\CmsPostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsPost extends Model
{
    /** @use HasFactory<CmsPostFactory> */
    use HasCategories, HasFactory, HasTags, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_SCHEDULED = 'scheduled';

    protected $fillable = [
        'slug', 'title', 'excerpt', 'body', 'featured_image',
        'featured_image_alt', 'author', 'meta_title', 'meta_description', 'meta_keywords', 'og_image',
        'is_published', 'status', 'published_at', 'views', 'reading_time_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(CmsPostRevision::class, 'post_id')->latest();
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

    public function estimateReadingTime(): int
    {
        $text = trim(strip_tags((string) $this->body));
        $words = max(1, str_word_count($text) + preg_match_all('/[\x{0600}-\x{06FF}]+/u', $text));

        return max(1, (int) ceil($words / 180));
    }

    /** @return array<string, mixed> */
    public function revisionSnapshot(): array
    {
        return $this->only([
            'slug', 'title', 'excerpt', 'body', 'featured_image',
            'featured_image_alt', 'author', 'meta_title', 'meta_description', 'meta_keywords',
            'og_image', 'is_published', 'status', 'published_at', 'reading_time_minutes',
        ]);
    }
}
