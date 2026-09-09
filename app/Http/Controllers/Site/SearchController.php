<?php

namespace App\Http\Controllers\Site;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use App\Services\SeoService;
use App\Services\SiteDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends SiteController
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $pages = $posts = $courses = collect();

        if ($q !== '') {
            $pages = CmsPage::query()->published()->where('title', 'like', "%{$q}%")->limit(20)->get();
            $posts = CmsPost::query()->published()->where('title', 'like', "%{$q}%")->limit(20)->get();
            $courses = ShopProduct::query()->published()->where('type', 'course')->where('title', 'like', "%{$q}%")->limit(20)->get();
        }

        $seo = app(SeoService::class)->meta([
            'title' => 'جستجو | '.config('cms.site_name_fa'),
            'description' => 'جستجو در محتوای سایت',
        ]);

        return $this->render('pages.search', compact('q', 'pages', 'posts', 'courses', 'seo'));
    }
}
