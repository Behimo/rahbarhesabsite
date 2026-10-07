<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SidebarRequest;
use App\Models\Category;
use App\Models\CmsSidebar;
use App\Services\CategoryService;
use App\Services\SidebarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SidebarController extends Controller
{
    public function __construct(
        private SidebarService $sidebars,
        private CategoryService $categories,
    ) {}

    public function index(): View
    {
        $sidebars = CmsSidebar::query()
            ->with('category')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(20);

        return view('admin.sidebars.index', compact('sidebars'));
    }

    public function create(): View
    {
        return view('admin.sidebars.form', $this->formData(new CmsSidebar([
            'is_active' => true,
            'target_mode' => CmsSidebar::MODE_PAGES,
            'source' => CmsSidebar::SOURCE_COURSE,
            'selection' => CmsSidebar::SELECTION_LATEST,
            'limit' => 5,
            'sort_order' => 0,
            'rules' => ['match' => 'all'],
        ])));
    }

    public function store(SidebarRequest $request): RedirectResponse
    {
        CmsSidebar::query()->create($request->sidebarAttributes());

        return redirect()->route('admin.sidebars.index')->with('success', 'سایدبار ایجاد شد.');
    }

    public function edit(CmsSidebar $sidebar): View
    {
        return view('admin.sidebars.form', $this->formData($sidebar));
    }

    public function update(SidebarRequest $request, CmsSidebar $sidebar): RedirectResponse
    {
        $sidebar->update($request->sidebarAttributes());

        return redirect()->route('admin.sidebars.index')->with('success', 'سایدبار به‌روزرسانی شد.');
    }

    public function destroy(CmsSidebar $sidebar): RedirectResponse
    {
        $sidebar->delete();

        return redirect()->route('admin.sidebars.index')->with('success', 'سایدبار حذف شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(CmsSidebar $sidebar): array
    {
        return [
            'sidebar' => $sidebar,
            'pageGroups' => $this->sidebars->pageGroups(),
            'productCategories' => $this->categories->flat(Category::TYPE_PRODUCT),
            'postCategories' => $this->categories->flat(Category::TYPE_POST),
        ];
    }
}
