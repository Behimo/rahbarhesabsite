<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsPost extends Model
{
    /** @use HasFactory<\Database\Factories\CmsPostFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'slug', 'title', 'excerpt', 'body', 'featured_image',
        'author', 'meta_title', 'meta_description', 'meta_keywords', 'og_image',
        'is_published', 'status', 'published_at', 'views',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CmsCategory::class, 'category_id');
    }

    public function taxonomyTerms(): MorphToMany
    {
        return $this->morphToMany(CmsTaxonomyTerm::class, 'termable', 'cms_termables', 'termable_id', 'term_id');
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
