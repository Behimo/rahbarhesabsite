<?php

namespace App\Http\Controllers\Site;

use App\Models\Category;
use App\Models\CmsPost;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends SiteController
{
    public function index(Request $request): View
    {
        $seo = $this->seo->meta([
            'title' => 'بلاگ راهبر حساب | مقالات و راهنماها',
            'description' => 'مقالات، راهنماها و بخشنامه‌های حسابداری، مالیات و خدمات مالی.',
            'keywords' => 'بلاگ راهبر حساب, حسابداری, مالیات, آموزش',
        ]);

        $activeCategory = $request->string('category')->toString() ?: null;
        $category = $activeCategory
            ? app(CategoryService::class)->findByPath(Category::TYPE_POST, $activeCategory)
            : null;
        $categoryIds = $category ? $category->descendantsAndSelf()->pluck('id') : collect();

        $posts = CmsPost::query()
            ->published()
            ->with('categories')
            ->when($category, fn ($q) => $q->whereHas(
                'categories',
                fn ($related) => $related->whereIn('categories.id', $categoryIds)
            ))
            ->when($activeCategory && ! $category, fn ($q) => $q->whereRaw('0 = 1'))
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(9)
            ->withQueryString();

        $categories = app(CategoryService::class)->flat(Category::TYPE_POST, true);

        $featured = $posts->isNotEmpty() && $posts->currentPage() === 1
            ? $posts->getCollection()->first()
            : null;

        return $this->render('pages.blog.index', [
            'seo' => $seo,
            'posts' => $posts,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'featured' => $featured,
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'بلاگ', 'url' => route('blog.index')],
                ]),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $post = CmsPost::query()->published()->where('slug', $slug)->with(['categories.ancestors', 'tags'])->firstOrFail();

        $post->increment('views');

        $seo = $this->seo->meta([
            'title' => $post->meta_title ?: ($post->title.' | بلاگ راهبر حساب'),
            'description' => $post->meta_description ?: $post->excerpt,
            'keywords' => $post->meta_keywords,
            'og_title' => $post->meta_title ?: $post->title,
            'og_image' => $post->og_image ?: $post->featured_image,
        ]);

        $related = CmsPost::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->when($post->category, fn ($q) => $q->whereHas(
                'categories',
                fn ($category) => $category->where('categories.id', $post->category->id)
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
                    ['name' => $post->title, 'url' => route('blog.show', $post->slug)],
                ]),
                $this->articleSchema($post),
            ],
        ]);
    }

    private function articleSchema(CmsPost $post): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'datePublished' => $post->published_at?->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author ?: config('cms.site_name_fa'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('cms.site_name'),
            ],
        ];
    }
}
