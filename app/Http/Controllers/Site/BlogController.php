<?php

namespace App\Http\Controllers\Site;

use App\Models\CafeQuestion;
use App\Models\Category;
use App\Models\CmsPost;
use App\Models\Tag;
use App\Services\CategoryService;
use App\Services\PermalinkService;
use App\Services\SidebarService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
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

        $canonicalPath = match (true) {
            (bool) $categoryModel => $permalinks->category($categoryModel, $request->integer('page') ?: null),
            (bool) $tagModel => $permalinks->tag($tagModel, $request->integer('page') ?: null),
            default => $permalinks->blogIndex($request->integer('page') ?: null),
        };

        $categoryIds = $categoryModel ? $categoryModel->descendantsAndSelf()->pluck('id') : collect();

        $posts = $tagModel
            ? $this->taggedItems($tagModel)
            : CmsPost::query()
                ->published()
                ->with('categories')
                ->when($categoryModel, fn ($q) => $q->whereHas(
                    'categories',
                    fn ($related) => $related->whereIn('categories.id', $categoryIds)
                ))
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->paginate(config('seo.per_page.posts'))->withQueryString();

        abort_if($posts->currentPage() > $posts->lastPage(), 404);

        // Rank Math templates of the live site: archives "%term% Archives %page% - %sitename%",
        // tags render without %page%, the posts page has its own title/description.
        $page = $posts->currentPage();
        $seo = [
            'title' => match (true) {
                (bool) $categoryModel => $categoryModel->meta_title
                    ?: $this->seo->wpTitle($categoryModel->name.' Archives', $page, $posts->lastPage()),
                (bool) $tagModel => $this->seo->wpTitle($tagModel->name.' Archives'),
                default => $this->seo->wpTitle('نوشته های راهبر حساب', $page, $posts->lastPage(), ' اخبار و مقالات حسابداری در '.config('cms.site_name_fa')),
            },
            'description' => match (true) {
                (bool) $categoryModel => $categoryModel->meta_description ?: ($categoryModel->description ?: 'مقالات، راهنماها و بخشنامه‌های حسابداری، مالیات و خدمات مالی.'),
                (bool) $tagModel => 'مقالات، راهنماها و پرسش و پاسخ‌های '.$tagModel->name.' در '.config('cms.site_name_fa').'.',
                default => 'نوشته های راهبر حساب تمامی اخبار و مقالات کاربردی مرتبط با حسابداری را با ما تجربه کنید.',
            },
            'keywords' => 'بلاگ راهبر حساب, حسابداری, مالیات, آموزش',
            'canonical' => $permalinks->absolute($canonicalPath),
            'robots' => $categoryModel?->robots,
        ];

        $categoryList = $categories->flat(Category::TYPE_POST, true);

        $pageTitle = $categoryModel?->name ?? $tagModel?->name ?? 'مقالات حسابداری';

        $breadcrumb = [
            ['name' => config('seo.defaults.home_label'), 'url' => route('home')],
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
            'sidebars' => app(SidebarService::class)->blogListing($request),
            'paginationBase' => $paginationBase,
            'structuredData' => [
                $this->seo->breadcrumbSchema($breadcrumb),
            ],
        ]);
    }

    /**
     * WordPress tag archives mix posts and Q&A entries (20 per page, newest first).
     */
    private function taggedItems(Tag $tag): LengthAwarePaginator
    {
        $hasTag = fn ($related) => $related->where('tags.id', $tag->id);
        $posts = CmsPost::query()->published()->whereHas('tags', $hasTag)
            ->select(['cms_posts.id', DB::raw("'post' as item_type"), DB::raw('cms_posts.published_at as sorted_at')])
            ->toBase();
        $questions = CafeQuestion::query()->published()->whereHas('tags', $hasTag)
            ->select(['cafe_questions.id', DB::raw("'question' as item_type"), DB::raw('cafe_questions.answered_at as sorted_at')])
            ->toBase();

        $page = DB::query()->fromSub($posts->unionAll($questions), 'tagged')
            ->orderByDesc('sorted_at')->orderByDesc('id')
            ->paginate(config('seo.per_page.posts'))->withQueryString();

        $rows = collect($page->items());
        $models = [
            'post' => CmsPost::query()->with('categories')
                ->whereIn('id', $rows->where('item_type', 'post')->pluck('id'))->get()->keyBy('id'),
            'question' => CafeQuestion::query()
                ->whereIn('id', $rows->where('item_type', 'question')->pluck('id'))->get()->keyBy('id'),
        ];

        return $page->setCollection($rows
            ->map(fn ($row) => $models[$row->item_type][$row->id] ?? null)
            ->filter()
            ->values());
    }

    public function show(string $category, string $slug, PermalinkService $permalinks): View
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

        // Wrong category segment: 404 (no hop). Canonical URL is the only public path.
        if (rawurldecode($category) !== $expectedCategory && $category !== $expectedCategory) {
            abort(404);
        }

        CmsPost::withoutTimestamps(fn () => $post->increment('views'));

        $seo = [
            'title' => $post->meta_title ?: $this->seo->wpTitle($post->title),
            'description' => $post->meta_description ?: $this->seo->excerptDescription($post->body),
            'keywords' => $post->meta_keywords,
            'og_title' => $post->meta_title ?: $post->title,
            'og_image' => $post->og_image ?: $post->featured_image,
            'canonical' => $post->canonical_url ?: $permalinks->postAbsolute($post),
            'robots' => $post->robots,
            'og_type' => 'article',
        ];

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
                    ['name' => config('seo.defaults.home_label'), 'url' => route('home')],
                    ['name' => 'بلاگ', 'url' => route('blog.index')],
                    ['name' => $post->title, 'url' => $permalinks->postAbsolute($post)],
                ]),
                $this->seo->articleSchema($post),
            ],
        ]);
    }
}
