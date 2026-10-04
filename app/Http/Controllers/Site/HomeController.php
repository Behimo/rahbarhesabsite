<?php

namespace App\Http\Controllers\Site;

use App\Models\ShopProduct;
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
        $content = $this->homeContent->all();
        $about = $content['about'];
        $sections = $content['sections'];

        $seo = $this->seo->forPage('home', [
            'title' => 'راهبر حساب | موسسه آموزش حسابداری و خدمات مالی',
            'description' => 'موسسه آموزش حسابداری راهبر حساب — دوره‌های کاربردی حسابداری و مالیات، خدمات مالی و مالیاتی در سراسر ایران.',
            'keywords' => 'راهبر حساب, آموزش حسابداری, مالیات, اظهارنامه, حسابداری, rahbarhesab',
            'og_title' => 'راهبر حساب — خالق رهبران حسابداری',
        ]);

        return $this->renderSystemPage('home', 'pages.home', [
            'heading_small' => $about['heading_small'],
            'heading' => $about['heading'],
            'description' => $about['description'],
            'banner_image' => $about['banner_image'],
            'coursesTitle' => $sections['courses_title'],
            'freeCoursesTitle' => $sections['free_courses_title'],
            'sectionTitle' => $sections['news_title'],
            'appDownloadUrl' => $content['app_download_url'],
            'faqs' => $content['faqs'],
            'fallbackNews' => $content['fallback_news'],
            'latestPosts' => $this->homeContent->latestPosts(8),
            'courses' => $this->publishedCourses(12),
            'freeCourses' => $this->publishedCourses(6, freeOnly: true),
            'seo' => $seo,
            'structuredData' => [
                $this->seo->organizationSchema(),
                $this->seo->websiteSchema(),
                $this->seo->faqSchema($content['faqs']),
            ],
        ]);
    }

    private function publishedCourses(int $limit, bool $freeOnly = false)
    {
        $query = ShopProduct::query()
            ->published()
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with(['course.instructor'])
            ->orderBy('sort_order');

        if ($freeOnly) {
            $query->where('price', 0);
        }

        return $query->take($limit)->get();
    }
}
