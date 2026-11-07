<?php

namespace App\Services\Cms;

use App\Models\CmsProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CmsProductRepository
{
    /**
     * Keep the existing cache of presented arrays, rather than caching Eloquent
     * models or changing the cache payload consumed by existing callers.
     */
    public function rememberPresented(callable $present): Collection
    {
        return Cache::remember('cms_products', 3600, fn () => $this->published()->map($present));
    }

    /** @return Collection<int, CmsProduct> */
    public function published(): Collection
    {
        return CmsProduct::query()->published()->orderBy('sort_order')->get();
    }

    public function findPublished(string $slug): ?CmsProduct
    {
        return CmsProduct::query()->published()->where('slug', $slug)->first();
    }
}
