<?php

namespace App\Http\Controllers\Site;

use App\Http\Requests\Site\StoreCafeAnswerRequest;
use App\Http\Requests\Site\StoreCafeQuestionRequest;
use App\Models\CafeQuestion;
use App\Support\PersianSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CafeQuestionController extends SiteController
{
    public function index(Request $request): View
    {
        $q = $request->string('q')->toString();
        $sort = $request->string('sort')->toString() ?: 'latest';

        if (! in_array($sort, ['latest', 'popular', 'most_answers'], true)) {
            $sort = 'latest';
        }

        $questions = CafeQuestion::query()
            ->published()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', '%'.$q.'%')
                        ->orWhere('body', 'like', '%'.$q.'%');
                });
            })
            ->when($sort === 'popular', fn ($query) => $query->orderByDesc('views_count')->orderByDesc('answered_at'))
            ->when($sort === 'most_answers', fn ($query) => $query->orderByDesc('answers_count')->orderByDesc('answered_at'))
            ->when($sort === 'latest', fn ($query) => $query->orderByDesc('answered_at')->orderByDesc('id'))
            ->paginate(12)
            ->withQueryString();

        $seo = $this->seo->meta([
            'title' => 'کافه سوال | راهبر حساب',
            'description' => 'پرسش و پاسخ تخصصی حسابداری، مالیات و امور مالی با کارشناسان راهبر حساب.',
            'keywords' => 'کافه سوال, سوال حسابداری, مالیات, راهبر حساب',
        ]);

        return $this->render('pages.cafe-soal.index', [
            'seo' => $seo,
            'questions' => $questions,
            'q' => $q,
            'sort' => $sort,
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'کافه سوال', 'url' => route('cafe-soal.index')],
                ]),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $question = CafeQuestion::query()
            ->published()
            ->where('slug', $slug)
            ->with([
                'user',
                'officialAnswer.user',
                'communityAnswers' => fn ($query) => $query->with('user')->orderBy('created_at'),
            ])
            ->firstOrFail();

        $question->increment('views_count');

        $official = $question->officialAnswer;
        $community = $question->communityAnswers;

        $seo = $this->seo->meta([
            'title' => $question->title.' | کافه سوال',
            'description' => \Illuminate\Support\Str::limit(strip_tags($question->body), 160),
        ]);

        return $this->render('pages.cafe-soal.show', [
            'seo' => $seo,
            'question' => $question,
            'official' => $official,
            'community' => $community,
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'کافه سوال', 'url' => route('cafe-soal.index')],
                    ['name' => $question->title, 'url' => route('cafe-soal.show', $question->slug)],
                ]),
            ],
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

        CafeQuestion::query()->create([
            'user_id' => $request->user()->id,
            'title' => $title,
            'slug' => $slug,
            'body' => $request->validated('body'),
            'status' => CafeQuestion::STATUS_PENDING,
        ]);

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
