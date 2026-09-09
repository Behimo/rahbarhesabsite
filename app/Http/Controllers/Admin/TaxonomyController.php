<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsTaxonomy;
use App\Models\CmsTaxonomyTerm;
use App\Services\TaxonomyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function storeTaxonomy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:cms_taxonomies,slug'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:category,tag'],
            'object_types' => ['nullable', 'array'],
        ]);

        CmsTaxonomy::query()->create($validated);

        return back()->with('success', 'taxonomy ایجاد شد.');
    }

    public function storeTerm(Request $request, CmsTaxonomy $taxonomy): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'alpha_dash'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:cms_taxonomy_terms,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $taxonomy->terms()->create($validated);

        return back()->with('success', 'دسته اضافه شد.');
    }

    public function destroyTerm(CmsTaxonomyTerm $term): RedirectResponse
    {
        $term->delete();

        return back()->with('success', 'دسته حذف شد.');
    }
}
