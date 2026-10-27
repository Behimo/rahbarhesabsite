<?php

namespace App\Services;

use App\Models\AccountingLaw;
use App\Models\CafeQuestion;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use App\Models\Tag;
use App\Support\RobotsDirectives;

class SitemapService
{
    public function __construct(private PermalinkService $permalinks, private CacheService $cache) {}

    public function posts(): array
    {
        return $this->cache->rememberSitemap('posts', $this->permalinks->rootUrl(), function () {
            $urls = [['loc' => $this->permalinks->absolute('/blog'), 'priority' => '0.8', 'changefreq' => 'daily']];
            $categoryIds = $tagIds = [];
            foreach (CmsPost::query()->published()->select(['id', 'slug', 'robots', 'updated_at'])
                ->with(['categories:id,slug', 'tags:id'])->lazyById(250) as $post) {
                if ($this->isNoindex($post->robots)) {
                    continue;
                }
                $urls[] = ['loc' => $this->permalinks->postAbsolute($post), 'priority' => '0.7',
                    'changefreq' => 'weekly', 'lastmod' => $post->updated_at?->toAtomString()];
                foreach ($post->categories as $category) {
                    $categoryIds[$category->id] = true;
                }
                foreach ($post->tags as $tag) {
                    $tagIds[$tag->id] = true;
                }
            }
            $urls = array_merge($urls, $this->categories(Category::TYPE_POST, $categoryIds));
            foreach (Tag::query()->whereIn('id', array_keys($tagIds))->select(['id', 'slug'])->lazyById(250) as $tag) {
                $urls[] = ['loc' => $this->permalinks->absolute($this->permalinks->tag($tag)), 'priority' => '0.5', 'changefreq' => 'weekly'];
            }

            return $urls;
        });
    }

    public function pages(): array
    {
        return $this->cache->rememberSitemap('pages', $this->permalinks->rootUrl(), function () {
            $urls = [];
            $static = [
                'home' => ['/', '1.0', 'daily'],
                'courses' => ['/product', '0.9', 'daily'],
                'laws' => ['/laws', '0.8', 'weekly'],
                'about' => ['/about-us', '0.7', 'monthly'],
                'contact' => ['/contact-us', '0.8', 'monthly'],
            ];
            $pages = CmsPage::query()->whereIn('slug', array_keys($static))
                ->get(['id', 'slug', 'robots', 'updated_at'])->keyBy('slug');
            $liveIds = CmsPage::query()->published()->whereIn('slug', array_keys($static))->pluck('id')->all();
            foreach ($static as $slug => [$path, $priority, $frequency]) {
                $page = $pages->get($slug);
                if ($page && (! in_array($page->id, $liveIds, true) || $this->isNoindex($page->robots))) {
                    continue;
                }
                $urls[] = ['loc' => $this->permalinks->absolute($path), 'priority' => $priority,
                    'changefreq' => $frequency, 'lastmod' => $page?->updated_at?->toAtomString()];
            }
            foreach (CmsPage::query()->published()
                ->select(['id', 'slug', 'robots', 'is_system', 'updated_at'])->lazyById(250) as $page) {
                if (isset($static[$page->slug]) || $page->is_system || $this->permalinks->isReservedPrefix($page->slug)
                    || $this->isNoindex($page->robots)) {
                    continue;
                }
                $urls[] = ['loc' => $this->permalinks->pageAbsolute($page), 'priority' => '0.6',
                    'changefreq' => 'monthly', 'lastmod' => $page->updated_at?->toAtomString()];
            }

            return $urls;
        });
    }

    public function courses(): array
    {
        return $this->cache->rememberSitemap('courses', $this->permalinks->rootUrl(), function () {
            $urls = $categoryIds = [];
            foreach (ShopProduct::query()->published()->where('type', 'course')
                ->select(['id', 'slug', 'robots', 'updated_at'])->with('categories:id,slug')->lazyById(250) as $course) {
                if ($this->isNoindex($course->robots)) {
                    continue;
                }
                $urls[] = ['loc' => $this->permalinks->productAbsolute($course), 'priority' => '0.9',
                    'changefreq' => 'weekly', 'lastmod' => $course->updated_at?->toAtomString()];
                foreach ($course->categories as $category) {
                    $categoryIds[$category->id] = true;
                }
            }

            return array_merge($urls, $this->categories(Category::TYPE_PRODUCT, $categoryIds));
        });
    }

    private function categories(string $type, array $populatedIds): array
    {
        $categories = Category::query()->ofType($type)
            ->get(['id', 'parent_id', 'type', 'slug', 'full_slug', 'is_active', 'robots', 'updated_at'])->keyBy('id');
        foreach (array_keys($populatedIds) as $id) {
            $seen = [];
            while ($category = $categories->get($id)) {
                if (isset($seen[$id])) {
                    break;
                }
                $seen[$id] = $populatedIds[$id] = true;
                $id = $category->parent_id;
            }
        }
        $urls = [];
        foreach ($categories as $category) {
            if ($category->is_active && isset($populatedIds[$category->id]) && ! $this->isNoindex($category->robots)) {
                $urls[] = ['loc' => $this->permalinks->absolute($this->permalinks->category($category)),
                    'priority' => $type === Category::TYPE_PRODUCT ? '0.7' : '0.6',
                    'changefreq' => 'weekly', 'lastmod' => $category->updated_at?->toAtomString()];
            }
        }

        return $urls;
    }

    public function laws(): array
    {
        return $this->cache->rememberSitemap('laws', $this->permalinks->rootUrl(), function () {
            $urls = [['loc' => $this->permalinks->absolute('/laws'), 'priority' => '0.8', 'changefreq' => 'weekly']];
            foreach (AccountingLaw::query()->published()->select(['id', 'slug', 'updated_at'])->lazyById(250) as $law) {
                $urls[] = ['loc' => $this->permalinks->absolute('/laws/'.$law->slug), 'priority' => '0.7',
                    'changefreq' => 'monthly', 'lastmod' => $law->updated_at?->toAtomString()];
            }

            return $urls;
        });
    }

    public function questions(): array
    {
        return $this->cache->rememberSitemap('questions', $this->permalinks->rootUrl(), function () {
            $urls = [['loc' => $this->permalinks->absolute('/questions'), 'priority' => '0.8', 'changefreq' => 'daily']];
            foreach (CafeQuestion::query()->published()->select(['id', 'slug', 'robots', 'updated_at'])->lazyById(250) as $question) {
                if ($this->isNoindex($question->robots)) {
                    continue;
                }
                $urls[] = ['loc' => $this->permalinks->questionAbsolute($question), 'priority' => '0.6',
                    'changefreq' => 'monthly', 'lastmod' => $question->updated_at?->toAtomString()];
            }

            return $urls;
        });
    }

    private function isNoindex(?string $robots): bool
    {
        return in_array('noindex', explode(', ', RobotsDirectives::normalize($robots)), true);
    }
}
