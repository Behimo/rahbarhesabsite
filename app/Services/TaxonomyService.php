<?php

namespace App\Services;

use App\Models\CmsCategory;
use App\Models\CmsTaxonomy;
use App\Models\CmsTaxonomyTerm;
use Illuminate\Database\Eloquent\Model;

class TaxonomyService
{
    public function __construct(private CacheService $cache) {}

    public function ensureDefaults(): void
    {
        $courseTax = CmsTaxonomy::query()->firstOrCreate(
            ['slug' => 'course-category'],
            [
                'name' => 'دسته‌بندی دوره',
                'type' => 'category',
                'object_types' => ['shop_product'],
            ]
        );

        CmsTaxonomy::query()->firstOrCreate(
            ['slug' => 'product-category'],
            [
                'name' => 'دسته‌بندی محصول',
                'type' => 'category',
                'object_types' => ['cms_product'],
            ]
        );

        CmsTaxonomy::query()->firstOrCreate(
            ['slug' => 'post-category'],
            [
                'name' => 'دسته‌بندی بلاگ',
                'type' => 'category',
                'object_types' => ['cms_post'],
            ]
        );

        if (CmsCategory::query()->exists() && $courseTax->terms()->count() === 0) {
            foreach (CmsCategory::query()->get() as $legacy) {
                CmsTaxonomyTerm::query()->firstOrCreate(
                    ['taxonomy_id' => $courseTax->id, 'slug' => $legacy->slug],
                    ['name' => $legacy->name, 'description' => $legacy->description]
                );
            }
        }
    }

    public function syncTerms(Model $model, array $termIds): void
    {
        if (method_exists($model, 'taxonomyTerms')) {
            $model->taxonomyTerms()->sync($termIds);
            $this->cache->flushTag(CacheService::TAG_TAXONOMY);
        }
    }

    public function termsFor(string $taxonomySlug)
    {
        return CmsTaxonomyTerm::query()
            ->whereHas('taxonomy', fn ($q) => $q->where('slug', $taxonomySlug))
            ->orderBy('sort_order')
            ->get();
    }
}
