<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomeContentRequest;
use App\Models\CmsPage;
use App\Services\HomeContentService;
use App\Services\HomePageDefaults;
use Illuminate\Http\RedirectResponse;

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

    public function update(HomeContentRequest $request): RedirectResponse
    {
        $this->homeContent->save($request->content());

        return back()->with('success', 'صفحه اصلی ذخیره شد.');
    }
}
