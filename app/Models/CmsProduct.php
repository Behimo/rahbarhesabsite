<?php

namespace App\Models;

use App\Models\Concerns\HasCategories;
use Illuminate\Database\Eloquent\Model;

class CmsProduct extends Model
{
    use HasCategories;

    protected $fillable = [
        'slug', 'title', 'subtitle', 'description', 'accent', 'visual', 'dashboard_image',
        'audience', 'features', 'cta', 'body', 'meta_title', 'meta_description',
        'meta_keywords', 'og_image', 'is_published', 'is_featured', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
