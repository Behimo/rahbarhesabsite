<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\CmsCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = CmsCategory::query()->withCount('posts')->orderBy('sort_order')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['sort_order'] = $validated['sort_order'] ?? ((int) CmsCategory::query()->max('sort_order') + 1);

        CmsCategory::query()->create($validated);

        return back()->with('success', 'دسته‌بندی ایجاد شد.');
    }

    public function update(CategoryRequest $request, CmsCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return back()->with('success', 'دسته‌بندی به‌روزرسانی شد.');
    }

    public function destroy(CmsCategory $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', 'دسته‌بندی حذف شد.');
    }
}
