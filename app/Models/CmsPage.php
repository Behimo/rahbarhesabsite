<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug', 'title', 'template', 'builder_enabled', 'meta_title', 'meta_description', 'meta_keywords',
        'og_image', 'robots', 'content', 'builder_content', 'is_published', 'status', 'published_at',
        'show_in_nav', 'is_system', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'builder_content' => 'array',
            'is_published' => 'boolean',
            'builder_enabled' => 'boolean',
            'show_in_nav' => 'boolean',
            'is_system' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(CmsPageRevision::class, 'page_id');
    }

    public function scopePublished($query)
    {
        return $query
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'published');
            });
    }
}
