<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaxonomyRequest;
use App\Http\Requests\Admin\TaxonomyTermRequest;
use App\Models\CmsTaxonomy;
use App\Models\CmsTaxonomyTerm;
use App\Services\TaxonomyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxonomyController extends Controller
{
    public function __construct(private TaxonomyService $taxonomy) {}

    public function index(): View
    {
        $this->taxonomy->ensureDefaults();

        return view('admin.taxonomies.index', [
            'taxonomies' => CmsTaxonomy::query()->with('terms')->orderBy('sort_order')->get(),
        ]);
    }

    public function storeTaxonomy(TaxonomyRequest $request): RedirectResponse
    {
        CmsTaxonomy::query()->create($request->validated());

        return back()->with('success', 'taxonomy ایجاد شد.');
    }

    public function storeTerm(TaxonomyTermRequest $request, CmsTaxonomy $taxonomy): RedirectResponse
    {
        $taxonomy->terms()->create($request->validated());

        return back()->with('success', 'دسته اضافه شد.');
    }

    public function destroyTerm(CmsTaxonomyTerm $term): RedirectResponse
    {
        $term->delete();

        return back()->with('success', 'دسته حذف شد.');
    }
}
