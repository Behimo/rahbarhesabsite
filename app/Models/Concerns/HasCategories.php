<?php

namespace App\Models\Concerns;

use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasCategories
{
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categoryable')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function getCategoryAttribute(): ?Category
    {
        $categories = $this->relationLoaded('categories')
            ? $this->categories
            : $this->categories()->get();

        return $categories->first(fn (Category $category) => (bool) $category->pivot->is_primary)
            ?? $categories->first();
    }

    /** @param  array<int, int|string>  $categoryIds */
    public function syncCategories(array $categoryIds, ?int $primaryId = null): void
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($categoryIds))));

        if ($primaryId && ! in_array($primaryId, $ids, true)) {
            $ids[] = $primaryId;
        }

        if ($ids === []) {
            $this->categories()->detach();

            return;
        }

        $primaryId = $primaryId && in_array($primaryId, $ids, true) ? $primaryId : $ids[0];
        $sync = [];

        foreach ($ids as $id) {
            $sync[$id] = ['is_primary' => $id === $primaryId];
        }

        $this->categories()->sync($sync);
    }
}
