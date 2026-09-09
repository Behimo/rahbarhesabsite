<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsTaxonomy extends Model
{
    protected $fillable = [
        'slug', 'name', 'type', 'object_types', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['object_types' => 'array'];
    }

    public function terms(): HasMany
    {
        return $this->hasMany(CmsTaxonomyTerm::class, 'taxonomy_id')->orderBy('sort_order');
    }
}
