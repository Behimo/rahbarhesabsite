<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\CmsPage;
use App\Services\SiteDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(private SiteDataService $siteData) {}

    public function index(): View
    {
        $pages = CmsPage::query()->orderBy('sort_order')->get();
        $defaults = $this->siteData->defaultPageMeta();

        return view('admin.pages.index', compact('pages', 'defaults'));
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new CmsPage(['is_published' => true, 'robots' => 'index, follow', 'template' => 'content'])]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = CmsPage::query()->create($request->pageAttributes());

        return redirect()->route('admin.pages.edit', $page)->with('success', 'صفحه ایجاد شد.');
    }

    public function edit(CmsPage $page): View
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(PageRequest $request, CmsPage $page): RedirectResponse
    {
        $page->update($request->pageAttributes());
        $this->siteData->clearCache();

        return redirect()->route('admin.pages.index')->with('success', 'صفحه با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(CmsPage $page): RedirectResponse
    {
        if ($page->is_system) {
            return back()->withErrors(['page' => 'صفحات سیستمی قابل حذف نیستند.']);
        }

        $page->delete();
        $this->siteData->clearCache();

        return redirect()->route('admin.pages.index')->with('success', 'صفحه حذف شد.');
    }
}
