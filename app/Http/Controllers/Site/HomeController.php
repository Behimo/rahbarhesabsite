<?php

namespace App\Http\Controllers\Site;

use App\Models\CmsPage;
use App\Models\ShopProduct;
use App\Services\BlockRenderer;
use App\Services\HomeContentService;
use App\Services\HomePageDefaults;
use App\Services\PageBuilderService;
use App\Services\SeoService;
use App\Services\SiteDataService;
use Illuminate\View\View;

class HomeController extends SiteController
{
    public function __construct(
        SiteDataService $siteData,
        SeoService $seo,
        private HomeContentService $homeContent,
        private HomePageDefaults $homeDefaults,
        private PageBuilderService $pageBuilder,
    ) {
        parent::__construct($siteData, $seo);
    }

    public function index(): View
    {
        $homePage = CmsPage::query()->where('slug', 'home')->first();
        $builderContent = $this->pageBuilder->resolveContent($homePage, 'home');
        $context = $this->homeContext();
        $bodyHtml = app(BlockRenderer::class)->render($builderContent, $context);

        $seo = $this->seo->meta([
            'title' => 'راهبر حساب | موسسه آموزش حسابداری و خدمات مالی',
            'description' => 'موسسه آموزش حسابداری راهبر حساب — دوره‌های کاربردی حسابداری و مالیات، خدمات مالی و مالیاتی در سراسر ایران.',
            'keywords' => 'راهبر حساب, آموزش حسابداری, مالیات, اظهارنامه, حسابداری, rahbarhesab',
            'og_title' => 'راهبر حساب — خالق رهبران حسابداری',
        ]);

        return $this->render('pages.sections', [
            'page' => $homePage,
            'bodyHtml' => $bodyHtml,
            'seo' => $seo,
            'structuredData' => [
                $this->seo->organizationSchema(),
                $this->seo->websiteSchema(),
                $this->seo->faqSchema($context['faqs'] ?? []),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function homeContext(): array
    {
        $faqs = $this->homeDefaults->defaultFaqs();

        return [
            'faqs' => $faqs,
            'fallbackNews' => $this->homeDefaults->defaultFallbackNews(),
            'latestPosts' => $this->homeContent->latestPosts(8),
            'courses' => ShopProduct::query()
                ->published()
                ->where('type', ShopProduct::TYPE_COURSE)
                ->with(['course.instructor'])
                ->orderBy('sort_order')
                ->take(12)
                ->get(),
            'freeCourses' => ShopProduct::query()
                ->published()
                ->where('type', ShopProduct::TYPE_COURSE)
                ->where('price', 0)
                ->take(6)
                ->get(),
        ];
    }
}
