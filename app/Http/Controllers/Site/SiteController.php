<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\BlockRenderer;
use App\Services\SeoService;
use App\Services\SiteDataService;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewResponse;

abstract class SiteController extends Controller
{
    public function __construct(
        protected SiteDataService $siteData,
        protected SeoService $seo,
    ) {
    }

    protected function render(string $view, array $data = []): ViewResponse
    {
        $themeView = 'theme::' . $view;

        if (View::exists($themeView)) {
            return view($themeView, $data);
        }
        return view($view, $data);
    }

    protected function renderSystemPage(string $slug, string $fallbackView, array $data = []): ViewResponse
    {
        $page = CmsPage::query()->where('slug', $slug)->first();
        $seo = $this->seo->forPage($slug, $data['seo'] ?? []);

        if ($page?->builder_enabled && !empty($page->builder_content)) {
            $bodyHtml = app(BlockRenderer::class)->render($page->builder_content);

            return $this->render('pages.cms-content', array_merge($data, [
                'page' => $page,
                'bodyHtml' => $bodyHtml,
                'seo' => $seo,
            ]));
        }

        return $this->render($fallbackView, array_merge($data, ['seo' => $seo, 'page' => $page]));
    }
}
