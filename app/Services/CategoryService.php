<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public function __construct(private CacheService $cache) {}

    /** @return Collection<int, Category> */
    public function flat(string $type, bool $activeOnly = false): Collection
    {
        return Cache::rememberForever($this->key($type, $activeOnly), function () use ($type, $activeOnly) {
            $items = Category::query()
                ->ofType($type)
                ->when($activeOnly, fn ($query) => $query->active())
                ->withCount('categoryables')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return $this->flatten($items);
        });
    }

    public function forget(?string $type = null): void
    {
        $types = $type ? [$type] : [Category::TYPE_PRODUCT, Category::TYPE_POST];

        foreach ($types as $item) {
            Cache::forget($this->key($item, true));
            Cache::forget($this->key($item, false));
        }

        $this->cache->flushTag(CacheService::TAG_TAXONOMY);
    }

    public function ensureDefaultTags(): void
    {
        foreach ([
            ['slug' => 'tax', 'name' => 'مالیات'],
            ['slug' => 'accounting', 'name' => 'حسابداری'],
            ['slug' => 'payroll', 'name' => 'حقوق و دستمزد'],
            ['slug' => 'vat', 'name' => 'ارزش افزوده'],
        ] as $tag) {
            Tag::query()->firstOrCreate(
                ['slug' => $tag['slug']],
                ['name' => $tag['name']]
            );
        }
    }

    public function findByPath(string $type, string $path): ?Category
    {
        return Category::query()
            ->ofType($type)
            ->active()
            ->where(function ($query) use ($path) {
                $query->where('full_slug', $path)->orWhere('slug', $path);
            })
            ->orderByRaw('case when full_slug = ? then 0 else 1 end', [$path])
            ->first();
    }

    /**
     * @param  Collection<int, Category>  $items
     * @return Collection<int, Category>
     */
    private function flatten(Collection $items): Collection
    {
        $byParent = $items->groupBy(fn (Category $category) => $category->parent_id ?? 0);

        $walk = function (int $parentId, int $depth) use (&$walk, $byParent) {
            $flat = collect();

            foreach ($byParent->get($parentId, collect()) as $category) {
                $category->treeDepth = $depth;
                $flat->push($category);
                $flat = $flat->merge($walk($category->id, $depth + 1));
            }

            return $flat;
        };

        return $walk(0, 0);
    }

    private function key(string $type, bool $activeOnly): string
    {
        return 'categories.tree.'.$type.'.'.($activeOnly ? 'active' : 'all');
    }
}
