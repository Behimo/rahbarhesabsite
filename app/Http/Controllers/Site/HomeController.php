<?php

namespace App\Http\Controllers\Site;

use App\Models\CmsPage;
use App\Models\ShopProduct;
use App\Services\BlockRenderer;
use App\Services\HomeContentService;
use App\Services\SeoService;
use App\Services\SiteDataService;
use Illuminate\View\View;

class HomeController extends SiteController
{
    public function __construct(
        SiteDataService $siteData,
        SeoService $seo,
        private HomeContentService $homeContent,
    ) {
        parent::__construct($siteData, $seo);
    }

    public function index(): View
    {
        $homePage = CmsPage::query()->where('slug', 'home')->first();

        if ($homePage?->builder_enabled && !empty($homePage->builder_content)) {
            $bodyHtml = app(BlockRenderer::class)->render($homePage->builder_content);
            $seo = $this->seo->forPage('home');

            return $this->render('pages.cms-content', [
                'page' => $homePage,
                'bodyHtml' => $bodyHtml,
                'seo' => $seo,
                'structuredData' => [
                    $this->seo->organizationSchema(),
                    $this->seo->websiteSchema(),
                ],
            ]);
        }

        return $this->rahbarHesabHome();
    }

    private function rahbarHesabHome(): View
    {
        $seo = $this->seo->meta([
            'title' => 'راهبر حساب | موسسه آموزش حسابداری و خدمات مالی',
            'description' => 'موسسه آموزش حسابداری راهبر حساب — دوره‌های کاربردی حسابداری و مالیات، خدمات مالی و مالیاتی در سراسر ایران.',
            'keywords' => 'راهبر حساب, آموزش حسابداری, مالیات, اظهارنامه, حسابداری, rahbarhesab',
            'og_title' => 'راهبر حساب — خالق رهبران حسابداری',
        ]);

        $courses = ShopProduct::query()
            ->published()
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with(['course.instructor'])
            ->orderBy('sort_order')
            ->take(12)
            ->get();

        $freeCourses = ShopProduct::query()
            ->published()
            ->where('type', ShopProduct::TYPE_COURSE)
            ->where('price', 0)
            ->take(6)
            ->get();

        $faqs = [
            ['q' => 'هزینه حمل مواد مستقیم در اظهارنامه مالیاتی چگونه ثبت می‌شود؟', 'a' => 'هزینه حمل مواد مستقیم باید در اظهارنامه مالیاتی طبق دستورالعمل سازمان امور مالیاتی ثبت شود.'],
            ['q' => 'مهلت ارسال اظهارنامه ارزش افزوده چه زمانی است؟', 'a' => 'مهلت ارسال اظهارنامه ارزش افزوده معمولاً یک ماه پس از پایان هر فصل است.'],
            ['q' => 'آیا دوره‌ها مدرک معتبر دارند؟', 'a' => 'بله، دوره‌های راهبر حساب با مدارک معتبر فنی‌حرفه‌ای و CIP ارائه می‌شوند.'],
        ];

        return $this->render('pages.home', [
            'seo' => $seo,
            'courses' => $courses,
            'freeCourses' => $freeCourses,
            'latestPosts' => $this->homeContent->latestPosts(6),
            'features' => [
                ['icon' => '🎓', 'title' => '+۲۰ دوره کاربردی', 'desc' => 'آموزش عملی ویژه بازار کار'],
                ['icon' => '📜', 'title' => 'مدرک معتبر', 'desc' => 'فنی‌حرفه‌ای و CIP'],
                ['icon' => '💬', 'title' => 'پشتیبانی ۷/۲۴', 'desc' => 'پاسخگویی تخصصی'],
                ['icon' => '👨‍🏫', 'title' => '+۱۰۰ مشاور', 'desc' => 'خدمات مالی و مالیاتی'],
            ],
            'faqs' => $faqs,
            'structuredData' => [
                $this->seo->organizationSchema(),
                $this->seo->websiteSchema(),
                $this->seo->faqSchema($faqs),
            ],
        ]);
    }
}
