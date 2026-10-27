<?php

namespace App\Http\Controllers\Site;

use App\Http\Requests\Site\StoreCafeAnswerRequest;
use App\Http\Requests\Site\StoreCafeQuestionRequest;
use App\Models\CafeQuestion;
use App\Models\Category;
use App\Services\CategoryService;
use App\Services\PermalinkService;
use App\Support\PersianSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CafeQuestionController extends SiteController
{
    public function index(Request $request, CategoryService $categories, PermalinkService $permalinks): View
    {
        if ($request->route('page') !== null) {
            $request->merge(['page' => max(1, (int) $request->route('page'))]);
        }

        $q = $request->string('q')->toString();
        $sort = $request->string('sort')->toString() ?: 'latest';
        $categoryId = $request->integer('category') ?: null;

        if (! in_array($sort, ['latest', 'popular', 'most_answers'], true)) {
            $sort = 'latest';
        }

        $cafeCategories = $categories->flat(Category::TYPE_CAFE, true);

        if ($categoryId && ! $cafeCategories->contains(fn (Category $category) => $category->id === $categoryId)) {
            $categoryId = null;
        }

        $questions = CafeQuestion::query()
            ->published()
            ->with('categories')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', '%'.$q.'%')
                        ->orWhere('body', 'like', '%'.$q.'%');
                });
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->whereHas('categories', fn ($inner) => $inner->where('categories.id', $categoryId));
            })
            ->when($sort === 'popular', fn ($query) => $query->orderByDesc('views_count')->orderByDesc('answered_at'))
            ->when($sort === 'most_answers', fn ($query) => $query->orderByDesc('answers_count')->orderByDesc('answered_at'))
            ->when($sort === 'latest', fn ($query) => $query->orderByDesc('answered_at')->orderByDesc('id'))
            ->paginate(config('seo.per_page.questions'))
            ->withQueryString();

        abort_if($questions->currentPage() > $questions->lastPage() && $questions->currentPage() > 1, 404);
        $page = $questions->currentPage();
        $canonicalPath = $page > 1 ? '/questions/page/'.$page : '/questions';
        $seo = [
            'title' => 'کافه سوال راهبر حساب'.($page > 1 ? ' - صفحه '.$page.' از '.$questions->lastPage() : '').' - راهبر حساب',
            'canonical' => $permalinks->absolute($canonicalPath),
            'robots' => $request->has('q') || ($request->has('sort') && $request->input('sort') !== 'latest') || $request->has('category')
                ? 'noindex, follow' : null,
            'description' => 'پرسش و پاسخ تخصصی حسابداری، مالیات و امور مالی با کارشناسان راهبر حساب.',
            'keywords' => 'کافه سوال, سوال حسابداری, مالیات, راهبر حساب',
        ];

        return $this->render('pages.cafe-soal.index', [
            'seo' => $seo,
            'questions' => $questions,
            'q' => $q,
            'sort' => $sort,
            'categoryId' => $categoryId,
            'cafeCategories' => $cafeCategories,
            'paginationBase' => '/questions',
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => config('seo.defaults.home_label'), 'url' => route('home')],
                    ['name' => 'کافه سوال راهبر حساب', 'url' => route('cafe-soal.index')],
                ]),
            ],
        ]);
    }

    public function show(string $slug, PermalinkService $permalinks): View
    {
        $question = CafeQuestion::query()
            ->published()
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)
                    ->orWhere('slug', rawurldecode($slug))
                    ->orWhere('slug', rawurlencode($slug));
            })
            ->with([
                'user',
                'categories',
                'officialAnswer.user',
                'communityAnswers' => fn ($query) => $query->with(['user', 'replies.user'])->orderBy('created_at'),
            ])
            ->firstOrFail();

        CafeQuestion::withoutTimestamps(fn () => $question->increment('views_count'));

        $official = $question->officialAnswer;
        $community = $question->communityAnswers;

        $seo = [
            'title' => $question->meta_title ?: $this->seo->wpTitle($question->title),
            'description' => $question->meta_description ?: $this->seo->excerptDescription($question->bodyHtml()),
            'canonical' => $question->canonical_url ?: $permalinks->questionAbsolute($question),
            'robots' => $question->robots ?: 'index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large',
            'og_image' => $question->og_image ?: $question->featured_image ?: $question->video_poster,
            'og_type' => 'article',
        ];
        $structuredData = [
            $this->seo->breadcrumbSchema([
                ['name' => config('seo.defaults.home_label'), 'url' => route('home')],
                ['name' => 'کافه سوال راهبر حساب', 'url' => route('cafe-soal.index')],
                ['name' => $question->title, 'url' => $permalinks->questionAbsolute($question)],
            ]),
        ];
        if ($question->video_url && $question->video_poster) {
            $structuredData[] = $this->seo->videoSchema($question->title, $seo['description'],
                $question->video_url, $question->video_poster, $question->created_at);
        }

        return $this->render('pages.cafe-soal.show', [
            'seo' => $seo,
            'question' => $question,
            'official' => $official,
            'community' => $community,
            'structuredData' => $structuredData,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('cafe-soal.index', ['ask' => 1]);
    }

    public function store(StoreCafeQuestionRequest $request): RedirectResponse
    {
        $title = $request->validated('title');
        $slug = PersianSlug::unique(
            $title,
            fn (string $candidate) => CafeQuestion::withTrashed()->where('slug', $candidate)->exists(),
            'question'
        );

        $categoryId = (int) $request->validated('category_id');

        $question = CafeQuestion::query()->create([
            'user_id' => $request->user()->id,
            'title' => $title,
            'slug' => $slug,
            'body' => $request->validated('body'),
            'status' => CafeQuestion::STATUS_PENDING,
        ]);

        $question->syncCategories([$categoryId], $categoryId);

        return redirect()
            ->route('cafe-soal.index')
            ->with('success', 'سوال شما ثبت شد و پس از بررسی کارشناسان پاسخ داده می‌شود.');
    }

    public function storeAnswer(StoreCafeAnswerRequest $request, string $slug): RedirectResponse
    {
        $question = CafeQuestion::query()->published()->where('slug', $slug)->firstOrFail();

        $question->answers()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
            'is_official' => false,
        ]);

        return back()->with('success', 'پاسخ شما ثبت شد.');
    }
}
