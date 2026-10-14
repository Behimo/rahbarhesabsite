<?php

namespace App\Http\Controllers\Site;

use App\Models\AccountingLaw;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LawController extends SiteController
{
    public function index(Request $request): View
    {
        $q = $request->string('q')->toString();
        $sort = $request->string('sort')->toString() ?: 'latest';
        $activeCategory = $request->string('category')->toString() ?: null;

        if (! in_array($sort, ['latest', 'popular', 'number'], true)) {
            $sort = 'latest';
        }

        $category = $activeCategory
            ? app(CategoryService::class)->findByPath(Category::TYPE_LAW, $activeCategory)
            : null;
        $categoryIds = $category ? $category->descendantsAndSelf()->pluck('id') : collect();

        $laws = AccountingLaw::query()
            ->published()
            ->with('categories')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', '%'.$q.'%')
                        ->orWhere('article_number', 'like', '%'.$q.'%')
                        ->orWhere('introduction', 'like', '%'.$q.'%')
                        ->orWhere('plain_language', 'like', '%'.$q.'%');
                });
            })
            ->when($category, fn ($query) => $query->whereHas(
                'categories',
                fn ($related) => $related->whereIn('categories.id', $categoryIds)
            ))
            ->when($activeCategory && ! $category, fn ($query) => $query->whereRaw('0 = 1'))
            ->when($sort === 'popular', fn ($query) => $query->orderByDesc('views')->orderByDesc('published_at'))
            ->when($sort === 'number', fn ($query) => $query->orderBy('article_number')->orderByDesc('published_at'))
            ->when($sort === 'latest', fn ($query) => $query->orderByDesc('published_at')->orderByDesc('created_at'))
            ->paginate(12)
            ->withQueryString();

        $categories = app(CategoryService::class)->flat(Category::TYPE_LAW, true);

        $seo = $this->seo->meta([
            'title' => 'مواد قانونی حسابداری | راهبر حساب',
            'description' => 'متن مواد قانونی مرتبط با حسابداری، همراه با توضیح ساده، موارد استفاده، مثال و سیر تحول.',
            'keywords' => 'مواد قانونی, قانون حسابداری, مالیات, ماده قانونی',
        ]);

        return $this->render('pages.laws.index', [
            'seo' => $seo,
            'laws' => $laws,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'q' => $q,
            'sort' => $sort,
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'مواد قانونی', 'url' => route('laws.index')],
                ]),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $law = AccountingLaw::query()
            ->published()
            ->where('slug', $slug)
            ->with(['categories.ancestors', 'evolutions'])
            ->firstOrFail();

        $law->increment('views');

        $seo = $this->seo->meta([
            'title' => $law->meta_title ?: ($law->display_title.' | مواد قانونی'),
            'description' => $law->meta_description ?: $law->excerpt(),
            'keywords' => $law->meta_keywords,
            'og_title' => $law->meta_title ?: $law->display_title,
            'og_image' => $law->featured_image,
        ]);

        $related = AccountingLaw::query()
            ->published()
            ->with('categories')
            ->where('id', '!=', $law->id)
            ->when($law->category, fn ($query) => $query->whereHas(
                'categories',
                fn ($category) => $category->where('categories.id', $law->category->id)
            ))
            ->latest('published_at')
            ->take(3)
            ->get();

        return $this->render('pages.laws.show', [
            'seo' => $seo,
            'law' => $law,
            'related' => $related,
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'مواد قانونی', 'url' => route('laws.index')],
                    ['name' => $law->display_title, 'url' => route('laws.show', $law->slug)],
                ]),
                $this->articleSchema($law),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function articleSchema(AccountingLaw $law): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $law->display_title,
            'description' => $law->excerpt(),
            'datePublished' => $law->published_at?->toIso8601String(),
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('cms.site_name'),
            ],
        ];

        if ($law->featured_image) {
            $schema['image'] = $law->featured_image;
        }

        return $schema;
    }
}
