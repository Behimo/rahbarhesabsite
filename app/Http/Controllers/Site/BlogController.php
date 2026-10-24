<?php

namespace App\Http\Controllers\Site;

use App\Models\Category;
use App\Models\CmsPost;
use App\Models\Tag;
use App\Services\CategoryService;
use App\Services\PermalinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends SiteController
{
    public function index(Request $request, CategoryService $categories, PermalinkService $permalinks): View
    {
        $page = $request->route('page');
        $page = $page !== null ? max(1, (int) $page) : null;
        $category = $request->route('category');
        $tag = $request->route('tag');

        if ($page !== null) {
            $request->merge(['page' => $page]);
        }

        $categoryPath = is_string($category) && $category !== ''
            ? trim($category, '/')
            : ($request->string('category')->toString() ?: null);
        $tagSlug = is_string($tag) && $tag !== ''
            ? $tag
            : ($request->string('tag')->toString() ?: null);

        // Soft-404 guard: unknown taxonomy must 404, not empty 200.
        $categoryModel = null;
        if ($categoryPath) {
            $categoryModel = $categories->findByPath(Category::TYPE_POST, $categoryPath);
            abort_unless($categoryModel, 404);
        }

        $tagModel = null;
        if ($tagSlug) {
            $tagModel = Tag::query()->where('slug', $tagSlug)->first();
            abort_unless($tagModel, 404);
        }

        $title = 'بلاگ راهبر حساب | مقالات و راهنماها';
        $description = 'مقالات، راهنماها و بخشنامه‌های حسابداری، مالیات و خدمات مالی.';
        $canonicalPath = $permalinks->blogIndex($request->integer('page') ?: null);

        if ($categoryModel) {
            $title = ($categoryModel->meta_title ?: $categoryModel->name).' | بلاگ راهبر حساب';
            $description = $categoryModel->meta_description ?: $description;
            $canonicalPath = $permalinks->category($categoryModel, $request->integer('page') ?: null);
        } elseif ($tagModel) {
            $title = $tagModel->name.' | بلاگ راهبر حساب';
            $canonicalPath = $permalinks->tag($tagModel, $request->integer('page') ?: null);
        }

        $seo = $this->seo->meta([
            'title' => $title,
            'description' => $description,
            'keywords' => 'بلاگ راهبر حساب, حسابداری, مالیات, آموزش',
            'canonical' => $permalinks->absolute($canonicalPath),
        ]);

        $categoryIds = $categoryModel ? $categoryModel->descendantsAndSelf()->pluck('id') : collect();

        $posts = CmsPost::query()
            ->published()
            ->with('categories')
            ->when($categoryModel, fn ($q) => $q->whereHas(
                'categories',
                fn ($related) => $related->whereIn('categories.id', $categoryIds)
            ))
            ->when($tagModel, fn ($q) => $q->whereHas(
                'tags',
                fn ($related) => $related->where('tags.id', $tagModel->id)
            ))
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(12);

        $categoryList = $categories->flat(Category::TYPE_POST, true);

        $pageTitle = $categoryModel?->name ?? $tagModel?->name ?? 'مقالات حسابداری';

        $breadcrumb = [
            ['name' => 'صفحه اصلی', 'url' => route('home')],
            ['name' => $pageTitle, 'url' => $permalinks->absolute($canonicalPath)],
        ];

        $paginationBase = $categoryModel
            ? '/category/'.($categoryModel->full_slug ?: $categoryModel->slug)
            : ($tagModel ? '/tag/'.$tagModel->slug : '/blog');

        return $this->render('pages.blog.index', [
            'seo' => $seo,
            'posts' => $posts,
            'categories' => $categoryList,
            'activeCategory' => $categoryModel?->full_slug ?: $categoryModel?->slug,
            'activeTag' => $tagModel?->slug,
            'pageTitle' => $pageTitle,
            'sidebars' => app(\App\Services\SidebarService::class)->blogListing($request),
            'paginationBase' => $paginationBase,
            'structuredData' => [
                $this->seo->breadcrumbSchema($breadcrumb),
            ],
        ]);
    }

    public function show(string $category, string $slug, PermalinkService $permalinks): View|RedirectResponse
    {
        $post = CmsPost::query()
            ->published()
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)
                    ->orWhere('slug', rawurldecode($slug))
                    ->orWhere('slug', rawurlencode($slug));
            })
            ->with(['categories.ancestors', 'tags'])
            ->firstOrFail();

        $primary = $post->category;
        $expectedCategory = $primary?->slug ?: 'blog';

        if (rawurldecode($category) !== $expectedCategory && $category !== $expectedCategory) {
            return redirect()->to($permalinks->post($post), 301);
        }

        $post->increment('views');

        $seo = $this->seo->meta([
            'title' => $post->meta_title ?: ($post->title.' | بلاگ راهبر حساب'),
            'description' => $post->meta_description ?: $post->excerpt,
            'keywords' => $post->meta_keywords,
            'og_title' => $post->meta_title ?: $post->title,
            'og_image' => $post->og_image ?: $post->featured_image,
            'canonical' => $post->canonical_url ?: $permalinks->postAbsolute($post),
            'robots' => $post->robots,
        ]);

        $related = CmsPost::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->when($primary, fn ($q) => $q->whereHas(
                'categories',
                fn ($cat) => $cat->where('categories.id', $primary->id)
            ))
            ->latest('published_at')
            ->take(3)
            ->get();

        return $this->render('pages.blog.show', [
            'seo' => $seo,
            'post' => $post,
            'related' => $related,
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'بلاگ', 'url' => route('blog.index')],
                    ['name' => $post->title, 'url' => $permalinks->postAbsolute($post)],
                ]),
                $this->articleSchema($post, $permalinks),
            ],
        ]);
    }

    private function articleSchema(CmsPost $post, PermalinkService $permalinks): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->excerpt ?: null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'mainEntityOfPage' => $permalinks->postAbsolute($post),
            'image' => $post->featured_image ? $this->seo->meta(['og_image' => $post->featured_image])['og_image'] : null,
            'author' => [
                '@type' => 'Person',
                'name' => $post->author ?: config('cms.site_name_fa'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('cms.site_name'),
            ],
        ], fn ($value) => $value !== null && $value !== '');
    }
}
