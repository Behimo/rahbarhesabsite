<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\BlockRenderer;
use App\Services\PageBuilderService;
use App\Services\SeoService;
use App\Services\SiteDataService;
use App\Services\ThemeService;
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
        return app(ThemeService::class)->view($view, array_merge(
            $this->siteData->sharedViewData(),
            $data
        ));
    }

    protected function renderSystemPage(string $slug, string $fallbackView, array $data = []): ViewResponse
    {
        $page = CmsPage::query()->where('slug', $slug)->first();
        $seo = $this->seo->forPage($slug, $data['seo'] ?? []);
        $pageBuilder = app(PageBuilderService::class);
        $builderContent = $pageBuilder->resolveContent($page, $slug);

        if ($pageBuilder->shouldRenderBuilder($page, $slug)) {
            $bodyHtml = app(BlockRenderer::class)->render($builderContent, $data);
            $view = $pageBuilder->usesFullWidthLayout($page, $slug) ? 'pages.sections' : 'pages.cms-content';

            return $this->render($view, array_merge($data, [
                'page' => $page,
                'bodyHtml' => $bodyHtml,
                'seo' => $seo,
            ]));
        }

        return $this->render($fallbackView, array_merge($data, ['seo' => $seo, 'page' => $page]));
    }
}
