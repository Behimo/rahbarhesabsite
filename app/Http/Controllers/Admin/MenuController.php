<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuRequest;
use App\Http\Requests\Admin\MenuTreeRequest;
use App\Models\CmsMenu;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsProduct;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
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
            'linkSources' => $this->linkSources(),
        ]);
    }

    public function update(MenuRequest $request, CmsMenu $menu): RedirectResponse
    {
        $previousLocation = $menu->location;
        $menu->update($request->validated());
        $this->menus->forgetLocation($previousLocation);
        $this->menus->forgetLocation($menu->location);

        return back()->with('success', 'منو به‌روزرسانی شد.');
    }

    public function saveTree(MenuTreeRequest $request, CmsMenu $menu): JsonResponse
    {
        $this->menus->saveTree($menu, $request->validated('tree'));

        return response()->json([
            'success' => true,
            'message' => 'ساختار منو ذخیره شد.',
            'tree' => $this->menus->buildTree($menu),
        ]);
    }

    public function destroy(CmsMenu $menu): RedirectResponse
    {
        $location = $menu->location;
        $menu->delete();
        $this->menus->forgetLocation($location);

        return redirect()->route('admin.menus.index')->with('success', 'منو حذف شد.');
    }

    private function linkSources(): array
    {
        $map = function ($rows): array {
            return $rows->map(fn ($row) => [
                'title' => $row->title,
                'slug' => $row->slug,
            ])->all();
        };

        return [
            'pages' => Schema::hasTable('cms_pages')
                ? $map(CmsPage::query()->orderBy('title')->get(['title', 'slug']))
                : [],
            'posts' => Schema::hasTable('cms_posts')
                ? $map(CmsPost::query()->orderBy('title')->get(['title', 'slug']))
                : [],
            'courses' => Schema::hasTable('cms_products')
                ? $map(CmsProduct::query()->orderBy('title')->get(['title', 'slug']))
                : [],
        ];
    }
}
