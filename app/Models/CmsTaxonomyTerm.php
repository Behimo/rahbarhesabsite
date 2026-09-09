<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class CmsTaxonomyTerm extends Model
{
    protected $fillable = [
        'taxonomy_id', 'parent_id', 'slug', 'name', 'description',
        'meta_title', 'meta_description', 'sort_order',
    ];

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(CmsTaxonomy::class, 'taxonomy_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(CmsPost::class, 'termable', 'cms_termables', 'term_id');
    }

    public function shopProducts(): MorphToMany
    {
        return $this->morphedByMany(ShopProduct::class, 'termable', 'cms_termables', 'term_id');
    }

    public function cmsProducts(): MorphToMany
    {
        return $this->morphedByMany(CmsProduct::class, 'termable', 'cms_termables', 'term_id');
    }
}
