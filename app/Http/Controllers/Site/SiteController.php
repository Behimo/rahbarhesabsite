<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\BlockRenderer;
use App\Services\PageBuilderService;
use App\Services\SeoService;
use App\Services\SiteDataService;
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
        return view($view, array_merge(
            $this->siteData->sharedViewData(),
            $data
        ));
    }

    protected function renderSystemPage(string $slug, string $view, array $data = []): ViewResponse
    {
        $page = $data['page'] ?? CmsPage::query()->where('slug', $slug)->first();
        $seo = $this->seo->forPage($slug, $data['seo'] ?? []);
        $content = is_array($page?->content) ? $page->content : [];
        $pageBuilder = app(PageBuilderService::class);

        if ($pageBuilder->shouldRenderBuilder($page)) {
            $bodyHtml = app(BlockRenderer::class)->render($pageBuilder->resolveContent($page), $data);

            return $this->render('pages.sections', array_merge($data, [
                'page' => $page,
                'bodyHtml' => $bodyHtml,
                'seo' => $seo,
            ]));
        }

        return $this->render($view, array_merge($data, [
            'seo' => $seo,
            'page' => $page,
            'bodyHtml' => $data['bodyHtml'] ?? ($content['body_html'] ?? ''),
        ]));
    }
}
