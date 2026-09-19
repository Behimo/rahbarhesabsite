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
            ['q' => 'شناسه عمومی مشابه کالا و خدمات در سامانه مودیان', 'a' => 'شناسه عمومی کالا و خدمات از سامانه مودیان برای یکسان‌سازی اقلام فاکتور استفاده می‌شود.', 'href' => route('blog.index')],
            ['q' => 'پرداخت حقوق قبل از تهیه کد کارگاهی و ثبت بیمه از کارگاه غیره', 'a' => 'ثبت حقوق و بیمه باید مطابق مقررات تأمین اجتماعی و با کد کارگاهی معتبر انجام شود.', 'href' => route('blog.index')],
            ['q' => 'مهلت واکنش به جزئیات اطلاعات معاملات سامانه ارزش افزوده', 'a' => 'مهلت واکنش به جزئیات معاملات در سامانه ارزش افزوده طبق اطلاعیه سازمان امور مالیاتی است.', 'href' => route('blog.index')],
            ['q' => 'اعتراض به برگ تشخیص سیستمی', 'a' => 'اعتراض به برگ تشخیص سیستمی از طریق سامانه مربوط و در مهلت قانونی امکان‌پذیر است.', 'href' => route('blog.index')],
            ['q' => 'نحوه اصلاح صورتحساب الکترونیکی در سامانه مؤدیان', 'a' => 'اصلاح صورتحساب الکترونیکی با صدور صورتحساب اصلاحی یا ابطالی در سامانه مؤدیان انجام می‌شود.', 'href' => route('blog.index')],
            ['q' => 'نحوه ثبت هزینه های قابل قبول مالیاتی', 'a' => 'هزینه‌های قابل قبول باید مستند، مرتبط با فعالیت و مطابق قانون مالیات‌های مستقیم باشند.', 'href' => route('blog.index')],
        ];

        $fallbackNews = [
            ['title' => 'کارپوشه تجاری و غیر تجاری در سامانه مودیان', 'subtitle' => 'شناسایی حساب‌های تجاری و ایجاد پرونده'],
            ['title' => 'آخرین مهلت ارسال صورت معاملات فصلی', 'subtitle' => 'پاییز ۱۴۰۴'],
            ['title' => 'آخرین مهلت ارسال اظهارنامه مالیات بر ارزش افزوده', 'subtitle' => 'بهار ۱۴۰۵'],
            ['title' => 'بخشودگی جرائم و تقسیط بدهی', 'subtitle' => 'مودیان فراخوان شده ارسال صورتحساب الکترونیک'],
            ['title' => 'الزام پرداخت مالیات ۸ درصدی با صدور صورتحساب الکترونیکی', 'subtitle' => 'ویژه برخی اصناف'],
            ['title' => 'مالیات تراکنش های بانکی', 'subtitle' => 'سال ۱۴۰۴'],
            ['title' => 'تیپ شخصیتی مناسب شغل حسابداری بر اساس MBTI', 'subtitle' => 'عمومی'],
            ['title' => 'تمدید ارسال دفاتر الکترونیکی', 'subtitle' => 'نیمه دوم سال ۱۴۰۴'],
        ];

        return $this->render('pages.home', [
            'seo' => $seo,
            'courses' => $courses,
            'freeCourses' => $freeCourses,
            'latestPosts' => $this->homeContent->latestPosts(8),
            'fallbackNews' => $fallbackNews,
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
