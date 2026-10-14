<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasRecursiveRelationships, SoftDeletes;

    public const TYPE_PRODUCT = 'product';

    public const TYPE_POST = 'post';

    public const TYPE_CAFE = 'cafe';

    public const TYPE_LAW = 'law';

    public int $treeDepth = 0;

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_POST => 'بلاگ',
            self::TYPE_PRODUCT => 'محصول و دوره',
            self::TYPE_CAFE => 'کافه سوال',
            self::TYPE_LAW => 'مواد قانونی',
        ];
    }

    public static function typeLabel(string $type): string
    {
        return self::types()[$type] ?? $type;
    }

    public static function resolveType(string $type): string
    {
        return array_key_exists($type, self::types()) ? $type : self::TYPE_POST;
    }

    protected $fillable = [
        'parent_id', 'type', 'name', 'slug', 'full_slug', 'description', 'image',
        'sort_order', 'is_active', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(CmsPost::class, 'categoryable')->withPivot('is_primary')->withTimestamps();
    }

    public function shopProducts(): MorphToMany
    {
        return $this->morphedByMany(ShopProduct::class, 'categoryable')->withPivot('is_primary')->withTimestamps();
    }

    public function cafeQuestions(): MorphToMany
    {
        return $this->morphedByMany(CafeQuestion::class, 'categoryable')->withPivot('is_primary')->withTimestamps();
    }

    public function accountingLaws(): MorphToMany
    {
        return $this->morphedByMany(AccountingLaw::class, 'categoryable')->withPivot('is_primary')->withTimestamps();
    }

    public function categoryables(): HasMany
    {
        return $this->hasMany(Categoryable::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function save(array $options = [])
    {
        if ($this->isDirty('slug') || $this->isDirty('parent_id') || blank($this->full_slug)) {
            $this->full_slug = $this->composedFullSlug();
        }

        return parent::save($options);
    }

    public function composedFullSlug(): string
    {
        if (! $this->parent_id) {
            return (string) $this->slug;
        }

        $parentSlug = static::withTrashed()->whereKey($this->parent_id)->value('full_slug');

        return $parentSlug ? $parentSlug.'/'.$this->slug : (string) $this->slug;
    }
}
