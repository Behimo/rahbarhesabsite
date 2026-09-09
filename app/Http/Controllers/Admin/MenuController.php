<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsMenu;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(private MenuService $menus) {}

    public function index(): View
    {
        return view('admin.menus.index', [
            'menus' => CmsMenu::query()->withCount('allItems')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.menus.form', ['menu' => new CmsMenu]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:cms_menus,slug'],
            'location' => ['nullable', 'string', 'max:50'],
        ]);

        $menu = CmsMenu::query()->create($validated);

        return redirect()->route('admin.menus.edit', $menu)->with('success', 'منو ایجاد شد.');
    }

    public function edit(CmsMenu $menu): View
    {
        return view('admin.menus.form', [
            'menu' => $menu,
            'tree' => $this->menus->buildTree($menu),
        ]);
    }

    public function update(Request $request, CmsMenu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:cms_menus,slug,'.$menu->id],
            'location' => ['nullable', 'string', 'max:50'],
        ]);

        $menu->update($validated);

        return back()->with('success', 'منو به‌روزرسانی شد.');
    }

    public function saveTree(Request $request, CmsMenu $menu): JsonResponse
    {
        $validated = $request->validate(['tree' => ['required', 'array']]);
        $this->menus->saveTree($menu, $validated['tree']);

        return response()->json(['success' => true]);
    }

    public function destroy(CmsMenu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'منو حذف شد.');
    }
}
