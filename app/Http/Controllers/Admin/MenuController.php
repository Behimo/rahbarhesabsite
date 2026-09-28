<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuRequest;
use App\Http\Requests\Admin\MenuTreeRequest;
use App\Models\CmsMenu;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

    public function store(MenuRequest $request): RedirectResponse
    {
        $menu = CmsMenu::query()->create($request->validated());

        return redirect()->route('admin.menus.edit', $menu)->with('success', 'منو ایجاد شد.');
    }

    public function edit(CmsMenu $menu): View
    {
        return view('admin.menus.form', [
            'menu' => $menu,
            'tree' => $this->menus->buildTree($menu),
        ]);
    }

    public function update(MenuRequest $request, CmsMenu $menu): RedirectResponse
    {
        $menu->update($request->validated());

        return back()->with('success', 'منو به‌روزرسانی شد.');
    }

    public function saveTree(MenuTreeRequest $request, CmsMenu $menu): JsonResponse
    {
        $this->menus->saveTree($menu, $request->validated('tree'));

        return response()->json(['success' => true]);
    }

    public function destroy(CmsMenu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'منو حذف شد.');
    }
}
