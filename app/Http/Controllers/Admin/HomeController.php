<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\HomeContentService;
use App\Services\HomePageDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private HomeContentService $homeContent) {}

    public function edit(HomePageDefaults $defaults): RedirectResponse
    {
        $page = CmsPage::query()->firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'صفحه اصلی',
                'is_published' => true,
                'is_system' => true,
                'template' => 'system',
                'status' => 'published',
                'sort_order' => 1,
            ]
        );

        if (empty($page->builder_content['blocks'])) {
            $page->update([
                'builder_enabled' => true,
                'builder_content' => $defaults->builderContent(),
            ]);
        }

        return redirect()->route('admin.pages.builder', $page);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'heading_small' => ['nullable', 'string', 'max:200'],
            'heading' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:1000'],
            'banner_image' => ['nullable', 'string', 'max:500'],
            'courses_title' => ['nullable', 'string', 'max:200'],
            'free_courses_title' => ['nullable', 'string', 'max:200'],
            'news_title' => ['nullable', 'string', 'max:200'],
            'app_download_url' => ['nullable', 'string', 'max:500'],
            'faqs' => ['nullable', 'array', 'max:12'],
            'faqs.*.q' => ['nullable', 'string', 'max:300'],
            'faqs.*.a' => ['nullable', 'string', 'max:1000'],
            'faqs.*.href' => ['nullable', 'string', 'max:500'],
        ]);

        $faqs = array_values(array_filter($validated['faqs'] ?? [], fn ($faq) => ! empty($faq['q'])));

        $this->homeContent->save([
            'about' => [
                'heading_small' => $validated['heading_small'] ?? '',
                'heading' => $validated['heading'] ?? '',
                'description' => $validated['description'] ?? '',
                'banner_image' => $validated['banner_image'] ?? '',
            ],
            'sections' => [
                'courses_title' => $validated['courses_title'] ?? '',
                'free_courses_title' => $validated['free_courses_title'] ?? '',
                'news_title' => $validated['news_title'] ?? '',
            ],
            'app_download_url' => $validated['app_download_url'] ?? '',
            'faqs' => $faqs,
        ]);

        return back()->with('success', 'صفحه اصلی ذخیره شد.');
    }
}
