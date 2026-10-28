<?php

namespace App\Providers;

use App\Services\SeoService;
use App\Services\SidebarService;
use App\Services\SiteDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('layouts.site', function ($view) {
            $this->app->call(function (SiteDataService $siteData, SeoService $seo, SidebarService $sidebars, Request $request) use ($view) {
                $data = $view->getData();
                foreach ($siteData->sharedViewData() as $key => $value) {
                    if (! array_key_exists($key, $data)) {
                        $view->with($key, $value);
                    }
                }
                $view->with('seoMeta', $seo->meta($data['seo'] ?? []));
                $view->with('pageSidebars', $sidebars->current($request));
                $view->with('blogListing', $request->routeIs(['blog.index', 'blog.index.paged', 'blog.category', 'blog.category.paged', 'blog.tag', 'blog.tag.paged']));
                $view->with('innerPage', empty($data['fullBleedContent']) && ! $request->routeIs([
                    'home', 'about', 'contact', 'courses.index', 'courses.show', 'blog.*', 'cafe-soal.*', 'laws.*', 'panel.*',
                    'login', 'login.verify', 'cart.index', 'checkout.index', 'checkout.success', 'checkout.failed', 'search',
                ]));
                $view->with('blogSidebarLayout', $request->routeIs('blog.index'));
                $view->with('courseSidebarLayout', $request->routeIs('courses.index'));
            });
        });
    }
}
