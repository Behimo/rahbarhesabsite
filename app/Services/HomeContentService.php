<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsPost;
use Illuminate\Support\Facades\Cache;

class HomeContentService
{
    public function all(): array
    {
        return Cache::remember('cms_home_content', 3600, function () {
            $page = CmsPage::query()->where('slug', 'home')->first();
            $stored = is_array($page?->content) ? $page->content : [];

            return [
                'about' => array_merge($this->defaultAbout(), $stored['about'] ?? []),
                'sections' => array_merge($this->defaultSections(), $stored['sections'] ?? []),
                'app_download_url' => $stored['app_download_url'] ?? '#',
                'faqs' => $this->filledFaqs($stored['faqs'] ?? null),
                'fallback_news' => $this->defaultFallbackNews(),
            ];
        });
    }

    public function save(array $content): void
    {
        $page = CmsPage::query()->firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'صفحه اصلی',
                'meta_title' => 'راهبر حساب | موسسه آموزش حسابداری و خدمات مالی',
                'is_published' => true,
                'is_system' => true,
                'template' => 'system',
                'status' => 'published',
                'sort_order' => 1,
            ]
        );

        $page->update(['content' => $content]);
        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget('cms_home_content');
    }

    public function latestPosts(int $limit = 8)
    {
        return CmsPost::query()
            ->published()
            ->with('category')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    /** @return array<string, string> */
    public function defaultAbout(): array
    {
        return [
            'heading_small' => 'راهبر حساب؛ خالق رهبران حسابداری',
            'heading' => 'موسسه آموزشی حسابداری و خدمات مالی و مالیاتی',
            'description' => 'آموزش تخصصی حسابداری برای بازار کار و ارائه خدمات مالی و مالیاتی به حسابداران و کسب و کارها در سراسر ایران؛ برگزارکننده نخستین همایش ۱۰۰۰ نفره حسابداری در کشور',
            'banner_image' => '',
        ];
    }

    /** @return array<string, string> */
    public function defaultSections(): array
    {
        return [
            'courses_title' => 'جدیدترین دوره های ما',
            'free_courses_title' => 'دوره های رایگان',
            'news_title' => 'اخبار و بخشنامه های جدید',
        ];
    }

    /** @return array<int, array<string, string>> */
    public function defaultFaqs(): array
    {
        return [
            ['q' => 'شناسه عمومی مشابه کالا و خدمات در سامانه مودیان', 'a' => 'شناسه عمومی کالا و خدمات از سامانه مودیان برای یکسان‌سازی اقلام فاکتور استفاده می‌شود.', 'href' => route('blog.index')],
            ['q' => 'پرداخت حقوق قبل از تهیه کد کارگاهی و ثبت بیمه از کارگاه غیره', 'a' => 'ثبت حقوق و بیمه باید مطابق مقررات تأمین اجتماعی و با کد کارگاهی معتبر انجام شود.', 'href' => route('blog.index')],
            ['q' => 'مهلت واکنش به جزئیات اطلاعات معاملات سامانه ارزش افزوده', 'a' => 'مهلت واکنش به جزئیات معاملات در سامانه ارزش افزوده طبق اطلاعیه سازمان امور مالیاتی است.', 'href' => route('blog.index')],
            ['q' => 'اعتراض به برگ تشخیص سیستمی', 'a' => 'اعتراض به برگ تشخیص سیستمی از طریق سامانه مربوط و در مهلت قانونی امکان‌پذیر است.', 'href' => route('blog.index')],
            ['q' => 'نحوه اصلاح صورتحساب الکترونیکی در سامانه مؤدیان', 'a' => 'اصلاح صورتحساب الکترونیکی با صدور صورتحساب اصلاحی یا ابطالی در سامانه مؤدیان انجام می‌شود.', 'href' => route('blog.index')],
            ['q' => 'نحوه ثبت هزینه های قابل قبول مالیاتی', 'a' => 'هزینه‌های قابل قبول باید مستند، مرتبط با فعالیت و مطابق قانون مالیات‌های مستقیم باشند.', 'href' => route('blog.index')],
        ];
    }

    /** @return array<int, array<string, string>> */
    public function defaultFallbackNews(): array
    {
        return [
            ['title' => 'کارپوشه تجاری و غیر تجاری در سامانه مودیان', 'subtitle' => 'شناسایی حساب‌های تجاری و ایجاد پرونده'],
            ['title' => 'آخرین مهلت ارسال صورت معاملات فصلی', 'subtitle' => 'پاییز ۱۴۰۴'],
            ['title' => 'آخرین مهلت ارسال اظهارنامه مالیات بر ارزش افزوده', 'subtitle' => 'بهار ۱۴۰۵'],
            ['title' => 'بخشودگی جرائم و تقسیط بدهی', 'subtitle' => 'مودیان فراخوان شده ارسال صورتحساب الکترونیک'],
            ['title' => 'الزام پرداخت مالیات ۸ درصدی با صدور صورتحساب الکترونیکی', 'subtitle' => 'ویژه برخی اصناف'],
            ['title' => 'مالیات تراکنش های بانکی', 'subtitle' => 'سال ۱۴۰۴'],
            ['title' => 'تیپ شخصیتی مناسب شغل حسابداری بر اساس MBTI', 'subtitle' => 'عمومی'],
            ['title' => 'تمدید ارسال دفاتر الکترونیکی', 'subtitle' => 'نیمه دوم سال ۱۴۰۴'],
        ];
    }

    /** @param  array<int, array<string, mixed>>|null  $faqs
     * @return array<int, array<string, string>>
     */
    private function filledFaqs(?array $faqs): array
    {
        if ($faqs === null) {
            return $this->defaultFaqs();
        }

        $filled = array_values(array_filter($faqs, fn ($faq) => ! empty($faq['q'])));

        return $filled === [] ? $this->defaultFaqs() : $filled;
    }
}
