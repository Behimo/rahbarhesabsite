<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\CategoryService;

class CategoryObserver
{
    /** @var array<int, array{parent_id: ?int, ids: array<int, int>}> */
    private static array $reparentPlans = [];

    public function __construct(private CategoryService $categories) {}

    public function saved(Category $category): void
    {
        if ($category->wasChanged(['slug', 'parent_id'])) {
            $this->rebuildChildren($category);
        }

        $this->categories->forget($category->type);
    }

    public function deleting(Category $category): void
    {
        self::$reparentPlans[$category->getKey()] = [
            'parent_id' => $category->parent_id,
            'ids' => Category::withTrashed()->where('parent_id', $category->id)->pluck('id')->all(),
        ];
    }

    public function deleted(Category $category): void
    {
        $plan = self::$reparentPlans[$category->getKey()] ?? null;
        unset(self::$reparentPlans[$category->getKey()]);

        if ($plan && $plan['ids'] !== []) {
            Category::withTrashed()->whereIn('id', $plan['ids'])->update([
                'parent_id' => $plan['parent_id'],
            ]);

            $parentSlug = $plan['parent_id']
                ? Category::withTrashed()->whereKey($plan['parent_id'])->value('full_slug')
                : null;

            foreach (Category::withTrashed()->whereIn('id', $plan['ids'])->get() as $child) {
                $child->full_slug = $parentSlug ? $parentSlug.'/'.$child->slug : $child->slug;
                $child->saveQuietly();
                $this->rebuildChildren($child);
            }
        }

        $this->categories->forget($category->type);
    }

    public function restored(Category $category): void
    {
        $this->categories->forget($category->type);
    }

    private function rebuildChildren(Category $category): void
    {
        $children = Category::withTrashed()->where('parent_id', $category->id)->get();

        foreach ($children as $child) {
            $child->full_slug = $category->full_slug.'/'.$child->slug;
            $child->saveQuietly();
            $this->rebuildChildren($child);
        }
    }
}
