<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use App\Services\SiteDataService;
use App\Services\ThemeService;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewResponse;

abstract class SiteController extends Controller
{
    public function __construct(
        protected SiteDataService $siteData,
        protected SeoService $seo,
    ) {}

    protected function render(string $view, array $data = []): ViewResponse
    {
        app(ThemeService::class)->registerViews();

        $merged = array_merge($this->siteData->sharedViewData(), $data);
        $themeView = 'theme::'.$view;

        if (View::exists($themeView)) {
            return view($themeView, $merged);
        }

        return view($view, $merged);
    }
}
