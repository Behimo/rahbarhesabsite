<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ShopProduct extends Model
{
    public const TYPE_COURSE = 'course';

    public const TYPE_DIGITAL = 'digital';

    public const TYPE_PHYSICAL = 'physical';

    protected $fillable = [
        'slug', 'title', 'subtitle', 'description', 'price', 'sale_price', 'type',
        'featured_image', 'is_published', 'stock', 'meta_title', 'meta_description', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'price' => 'integer',
            'sale_price' => 'integer',
        ];
    }

    public function course(): HasOne
    {
        return $this->hasOne(Course::class);
    }

    public function taxonomyTerms(): MorphToMany
    {
        return $this->morphToMany(CmsTaxonomyTerm::class, 'termable', 'cms_termables', 'termable_id', 'term_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function effectivePrice(): int
    {
        return (int) ($this->sale_price ?? $this->price);
    }

    public function isFree(): bool
    {
        return $this->effectivePrice() === 0;
    }

    public function isCourse(): bool
    {
        return $this->type === self::TYPE_COURSE;
    }
}
