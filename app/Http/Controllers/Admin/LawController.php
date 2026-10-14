<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LawEvolutionRequest;
use App\Http\Requests\Admin\LawRequest;
use App\Models\AccountingLaw;
use App\Models\Category;
use App\Models\LawEvolution;
use App\Services\CategoryService;
use App\Services\LawEvolutionService;
use App\Services\SiteDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LawController extends Controller
{
    public function __construct(
        private CategoryService $categories,
        private LawEvolutionService $evolutions,
        private SiteDataService $siteData,
    ) {}

    public function index(Request $request): View
    {
        $trashed = $request->boolean('trashed');

        $laws = AccountingLaw::query()
            ->with(['categories'])
            ->when($trashed, fn ($q) => $q->onlyTrashed())
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%")
                        ->orWhere('article_number', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('category'), fn ($q) => $q->whereHas(
                'categories',
                fn ($category) => $category->where('categories.id', $request->integer('category'))
            ))
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->string('status')->toString();
                if ($status === 'draft') {
                    $q->where(function ($inner) {
                        $inner->where('is_published', false)->orWhere('status', AccountingLaw::STATUS_DRAFT);
                    });
                } elseif ($status === 'scheduled') {
                    $q->where('is_published', true)
                        ->where('published_at', '>', now());
                } elseif ($status === 'published') {
                    $q->published();
                }
            })
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.laws.index', [
            'laws' => $laws,
            'categories' => $this->categories->flat(Category::TYPE_LAW),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'category' => $request->input('category'),
                'status' => $request->string('status')->toString(),
                'trashed' => $trashed,
            ],
            'trashCount' => AccountingLaw::onlyTrashed()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.laws.form', $this->formData(new AccountingLaw([
            'is_published' => false,
            'status' => AccountingLaw::STATUS_DRAFT,
        ])));
    }

    public function store(LawRequest $request): RedirectResponse
    {
        $law = AccountingLaw::query()->create($request->lawAttributes());
        $categoryId = $request->categoryId();
        $law->syncCategories($categoryId ? [$categoryId] : [], $categoryId);

        return redirect()->route('admin.laws.edit', $law)->with('success', 'ماده قانونی ایجاد شد.');
    }

    public function edit(AccountingLaw $law): View
    {
        $law->load(['categories', 'evolutions']);

        return view('admin.laws.form', $this->formData($law));
    }

    public function update(LawRequest $request, AccountingLaw $law): RedirectResponse
    {
        $law->update($request->lawAttributes());
        $categoryId = $request->categoryId();
        $law->syncCategories($categoryId ? [$categoryId] : [], $categoryId);

        return redirect()->route('admin.laws.edit', $law)->with('success', 'ماده قانونی به‌روزرسانی شد.');
    }

    public function destroy(AccountingLaw $law): RedirectResponse
    {
        $law->delete();

        return redirect()->route('admin.laws.index')->with('success', 'ماده قانونی به سطل زباله منتقل شد.');
    }

    public function restore(int $id): RedirectResponse
    {
        $law = AccountingLaw::onlyTrashed()->findOrFail($id);
        $law->restore();

        return redirect()->route('admin.laws.edit', $law)->with('success', 'ماده قانونی بازیابی شد.');
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        $law = AccountingLaw::onlyTrashed()->findOrFail($id);
        $law->forceDelete();

        return redirect()->route('admin.laws.index', ['trashed' => 1])->with('success', 'ماده قانونی برای همیشه حذف شد.');
    }

    public function preview(AccountingLaw $law): View
    {
        $law->load(['categories.ancestors', 'evolutions']);

        $related = AccountingLaw::query()
            ->published()
            ->with('categories')
            ->where('id', '!=', $law->id)
            ->when($law->category, fn ($q) => $q->whereHas(
                'categories',
                fn ($category) => $category->where('categories.id', $law->category->id)
            ))
            ->latest('published_at')
            ->take(3)
            ->get();

        $seo = [
            'title' => ($law->meta_title ?: $law->display_title).' | پیش‌نمایش',
            'description' => $law->meta_description ?: $law->excerpt(),
            'robots' => 'noindex, nofollow',
        ];

        return view('pages.laws.show', array_merge(
            $this->siteData->sharedViewData(),
            [
                'law' => $law,
                'related' => $related,
                'seo' => $seo,
                'isPreview' => true,
            ]
        ));
    }

    public function storeEvolution(LawEvolutionRequest $request, AccountingLaw $law): RedirectResponse
    {
        $this->evolutions->create($law, $request->evolutionAttributes());

        return redirect()->route('admin.laws.edit', $law)->with('success', 'مورد سیر تحول افزوده شد.');
    }

    public function updateEvolution(LawEvolutionRequest $request, AccountingLaw $law, LawEvolution $evolution): RedirectResponse
    {
        abort_unless($evolution->accounting_law_id === $law->id, 404);

        $this->evolutions->update($evolution, $request->evolutionAttributes());

        return redirect()->route('admin.laws.edit', $law)->with('success', 'مورد سیر تحول به‌روزرسانی شد.');
    }

    public function destroyEvolution(AccountingLaw $law, LawEvolution $evolution): RedirectResponse
    {
        abort_unless($evolution->accounting_law_id === $law->id, 404);

        $this->evolutions->delete($evolution);

        return redirect()->route('admin.laws.edit', $law)->with('success', 'مورد سیر تحول حذف شد.');
    }

    /** @return array<string, mixed> */
    private function formData(AccountingLaw $law): array
    {
        return [
            'law' => $law,
            'categories' => $this->categories->flat(Category::TYPE_LAW),
        ];
    }
}
