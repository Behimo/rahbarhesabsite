<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\SiteDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    private const RESERVED_SLUGS = [
        'admin', 'blog', 'products', 'courses', 'cart', 'checkout', 'login', 'register', 'panel',
        'contact', 'services', 'about', 'why-bisan', 'sitemap.xml', 'robots.txt', 'api',
    ];

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

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePage($request);
        $page = CmsPage::query()->create($validated);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'صفحه ایجاد شد.');
    }

    public function edit(CmsPage $page): View
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, CmsPage $page): RedirectResponse
    {
        $validated = $this->validatePage($request, $page);
        $page->update($validated);
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

    private function validatePage(Request $request, ?CmsPage $page = null): array
    {
        $slugRule = ['required', 'string', 'max:100', 'alpha_dash'];
        if ($page) {
            $slugRule[] = 'unique:cms_pages,slug,'.$page->id;
        } else {
            $slugRule[] = 'unique:cms_pages,slug';
        }

        $validated = $request->validate([
            'slug' => $slugRule,
            'title' => ['required', 'string', 'max:200'],
            'template' => ['required', 'in:system,content,builder'],
            'builder_enabled' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:300'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'robots' => ['required', 'string', 'max:50'],
            'body_html' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! $page?->is_system && in_array($validated['slug'], self::RESERVED_SLUGS, true)) {
            abort(422, 'این آدرس رزرو شده است.');
        }

        $content = $page?->content ?? [];
        if ($request->filled('body_html')) {
            $content['body_html'] = $validated['body_html'];
        }
        unset($validated['body_html']);

        $validated['content'] = $content;
        $validated['is_published'] = $request->boolean('is_published');
        $validated['builder_enabled'] = $request->boolean('builder_enabled');
        $validated['show_in_nav'] = $request->boolean('show_in_nav');
        $validated['is_system'] = $page?->is_system ?? false;
        $validated['status'] = $validated['status'] ?? ($validated['is_published'] ? 'published' : 'draft');

        if (! $page) {
            $validated['template'] = 'content';
        } elseif ($page->is_system) {
            unset($validated['slug'], $validated['template']);
        }

        return $validated;
    }
}
