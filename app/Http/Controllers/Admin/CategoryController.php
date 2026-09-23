<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = CmsCategory::query()->withCount('posts')->orderBy('sort_order')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:cms_categories,slug'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? ((int) CmsCategory::query()->max('sort_order') + 1);

        CmsCategory::query()->create($validated);

        return back()->with('success', 'دسته‌بندی ایجاد شد.');
    }

    public function update(Request $request, CmsCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:cms_categories,slug,'.$category->id],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category->update($validated);

        return back()->with('success', 'دسته‌بندی به‌روزرسانی شد.');
    }

    public function destroy(CmsCategory $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', 'دسته‌بندی حذف شد.');
    }
}
