<?php

namespace App\Services\Wordpress;

use App\Models\Category;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use App\Models\Tag;
use Illuminate\Support\Str;

class TaxonomyImporter extends WordpressImporter
{
    public function countCategories(): int
    {
        return (int) $this->wp()->table('term_taxonomy')
            ->whereIn('taxonomy', ['product_cat', 'category', 'post_tag', 'product_tag'])
            ->count();
    }

    /**
     * @param  (callable(): void)|null  $onProgress
     * @return array{created: int, updated: int, skipped: int}
     */
    public function migrateCategories(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ([
            'product_cat' => Category::TYPE_PRODUCT,
            'category' => Category::TYPE_POST,
        ] as $taxonomy => $type) {
            $termIdMap = [];

            foreach ($this->wpTermsForTaxonomy($taxonomy) as $term) {
                $slug = $this->normalizeWpSlug((string) $term->slug);
                $name = trim((string) $term->name);

                if ($slug === '' || $this->isSkippedCategorySlug($slug) || $name === '') {
                    $skipped++;
                    $onProgress && $onProgress();

                    continue;
                }

                $parentId = null;
                if ((int) $term->parent > 0) {
                    $parentId = $termIdMap[(int) $term->parent] ?? null;
                }

                if ($dryRun) {
                    Category::query()
                        ->where('type', $type)
                        ->where(function ($q) use ($slug, $parentId) {
                            $q->where('slug', $slug);
                            $parentId
                                ? $q->where('parent_id', $parentId)
                                : $q->whereNull('parent_id');
                        })
                        ->exists() ? $updated++ : $created++;
                    $onProgress && $onProgress();

                    continue;
                }

                $category = Category::withTrashed()
                    ->where('type', $type)
                    ->where(function ($q) use ($slug, $parentId) {
                        $q->where('slug', $slug);
                        $parentId
                            ? $q->where('parent_id', $parentId)
                            : $q->whereNull('parent_id');
                    })
                    ->first();

                if (! $category) {
                    $category = Category::withTrashed()
                        ->where('type', $type)
                        ->where('full_slug', $slug)
                        ->whereNull('parent_id')
                        ->first();
                }

                $payload = [
                    'parent_id' => $parentId,
                    'type' => $type,
                    'name' => mb_substr($name, 0, 191),
                    'slug' => mb_substr($slug, 0, 191),
                    'description' => $term->description ?: null,
                    'is_active' => true,
                    'robots' => $this->termRobots((int) $term->term_id),
                ];

                if ($category) {
                    if ($category->trashed()) {
                        $category->restore();
                    }
                    $category->fill($payload)->save();
                    $updated++;
                } else {
                    $category = Category::query()->create($payload);
                    $created++;
                }

                $termIdMap[(int) $term->term_id] = $category->id;
                $onProgress && $onProgress();
            }
        }

        $tagStats = $this->migrateTags($dryRun, $onProgress);

        return [
            'created' => $created + $tagStats['created'],
            'updated' => $updated + $tagStats['updated'],
            'skipped' => $skipped + $tagStats['skipped'],
            'tags_created' => $tagStats['created'],
            'tags_updated' => $tagStats['updated'],
        ];
    }

    /**
     * Import WP post_tag / product_tag into local tags table.
     *
     * @param  (callable(): void)|null  $onProgress
     * @return array{created: int, updated: int, skipped: int}
     */
    public function migrateTags(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (['post_tag', 'product_tag'] as $taxonomy) {
            foreach ($this->wpTermsForTaxonomy($taxonomy) as $term) {
                $slug = $this->normalizeWpSlug((string) $term->slug);
                $name = trim((string) $term->name);

                if ($slug === '' || $name === '') {
                    $skipped++;
                    $onProgress && $onProgress();

                    continue;
                }

                $slug = mb_substr($slug, 0, 191);

                if ($dryRun) {
                    Tag::query()->where('slug', $slug)->exists() ? $updated++ : $created++;
                    $onProgress && $onProgress();

                    continue;
                }

                $tag = Tag::query()->where('slug', $slug)->first();
                if ($tag) {
                    $tag->fill(['name' => mb_substr($name, 0, 191)])->save();
                    $updated++;
                } else {
                    Tag::query()->create([
                        'name' => mb_substr($name, 0, 191),
                        'slug' => $slug,
                    ]);
                    $created++;
                }

                $onProgress && $onProgress();
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * Rebuild category/tag pivots for already-migrated courses and posts.
     *
     * @param  (callable(): void)|null  $onProgress
     * @return array{products_linked: int, posts_linked: int, product_category_links: int, post_category_links: int, post_tag_links: int, missing_products: int, missing_posts: int}
     */
    public function migrateTermLinks(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $productsLinked = 0;
        $postsLinked = 0;
        $productCategoryLinks = 0;
        $postCategoryLinks = 0;
        $postTagLinks = 0;
        $missingProducts = 0;
        $missingPosts = 0;

        $products = $this->wp()->table('posts')
            ->where('post_type', 'product')
            ->whereIn('post_status', ['publish', 'private'])
            ->orderBy('ID')
            ->get(['ID', 'post_name', 'post_title']);

        foreach ($products as $post) {
            $rawSlug = (string) ($post->post_name ?: '');
            $slug = $this->normalizeWpSlug($rawSlug) ?: (Str::slug((string) $post->post_title).'-'.$post->ID);
            $product = ShopProduct::query()
                ->where(function ($q) use ($slug, $rawSlug) {
                    $q->where('slug', $slug);
                    if ($rawSlug !== '' && $rawSlug !== $slug) {
                        $q->orWhere('slug', $rawSlug);
                    }
                })
                ->first();

            if (! $product) {
                $missingProducts++;
                $onProgress && $onProgress();

                continue;
            }

            $catIds = $this->resolveCategoryIdsForWpObject((int) $post->ID, 'product_cat', Category::TYPE_PRODUCT);
            if (! $dryRun) {
                $this->syncModelCategories($product, (int) $post->ID, 'product_cat', Category::TYPE_PRODUCT);
            }
            $productsLinked++;
            $productCategoryLinks += count($catIds);
            $onProgress && $onProgress();
        }

        $posts = $this->wp()->table('posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get(['ID', 'post_name', 'post_title']);

        foreach ($posts as $post) {
            $rawSlug = (string) ($post->post_name ?: '');
            $slug = $this->normalizeWpSlug($rawSlug) ?: (Str::slug((string) $post->post_title).'-'.$post->ID);
            $slug = mb_substr($slug, 0, 180);

            $cmsPost = CmsPost::withTrashed()
                ->where(function ($q) use ($slug, $rawSlug) {
                    $q->where('slug', $slug);
                    if ($rawSlug !== '' && $rawSlug !== $slug) {
                        $q->orWhere('slug', $rawSlug);
                    }
                })
                ->first();

            if (! $cmsPost) {
                $missingPosts++;
                $onProgress && $onProgress();

                continue;
            }

            $catIds = $this->resolveCategoryIdsForWpObject((int) $post->ID, 'category', Category::TYPE_POST);
            $tagIds = $this->resolveTagIdsForWpObject((int) $post->ID, 'post_tag');

            if (! $dryRun) {
                if ($cmsPost->trashed()) {
                    $cmsPost->restore();
                }
                $this->syncModelCategories($cmsPost, (int) $post->ID, 'category', Category::TYPE_POST);
                $cmsPost->syncTags($tagIds);
            }

            $postsLinked++;
            $postCategoryLinks += count($catIds);
            $postTagLinks += count($tagIds);
            $onProgress && $onProgress();
        }

        return [
            'products_linked' => $productsLinked,
            'posts_linked' => $postsLinked,
            'product_category_links' => $productCategoryLinks,
            'post_category_links' => $postCategoryLinks,
            'post_tag_links' => $postTagLinks,
            'missing_products' => $missingProducts,
            'missing_posts' => $missingPosts,
        ];
    }
}
