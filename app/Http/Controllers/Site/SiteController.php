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
    ) {}

    protected function render(string $view, array $data = []): ViewResponse
    {
        return view($view, $data);
    }

    protected function renderSystemPage(string $slug, string $view, array $data = []): ViewResponse
    {
        $page = array_key_exists('page', $data) ? $data['page'] : CmsPage::query()->where('slug', $slug)->first();
        $seo = array_replace($this->siteData->pageMeta($slug, $page, loaded: true),
            array_filter($data['seo'] ?? [], fn ($value) => $value !== null && $value !== ''));
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
