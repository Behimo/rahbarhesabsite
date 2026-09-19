<?php

namespace App\Services;

class HomePageDefaults
{
    /** @return array<string, mixed> */
    public function builderContent(): array
    {
        return [
            'blocks' => [
                ['type' => 'hero', 'settings' => []],
                ['type' => 'feature-split', 'settings' => ['layout' => 'app']],
                ['type' => 'features', 'settings' => []],
                ['type' => 'courses', 'settings' => [
                    'title' => 'جدیدترین دوره های ما',
                    'section_id' => 'latestCourses',
                    'limit' => 12,
                    'filter' => 'latest',
                ]],
                ['type' => 'feature-split', 'settings' => ['layout' => 'finance']],
                ['type' => 'courses', 'settings' => [
                    'title' => 'دوره های رایگان',
                    'section_id' => 'freeCourses',
                    'section_class' => 'free-courses-section',
                    'limit' => 6,
                    'filter' => 'free',
                ]],
                ['type' => 'faq', 'settings' => []],
                ['type' => 'posts', 'settings' => []],
                ['type' => 'feature-split', 'settings' => ['layout' => 'system']],
                ['type' => 'feature-split', 'settings' => ['layout' => 'instagram']],
                ['type' => 'feature-split', 'settings' => ['layout' => 'mentor']],
                ['type' => 'feature-split', 'settings' => ['layout' => 'experiences']],
                ['type' => 'feature-split', 'settings' => ['layout' => 'partners']],
            ],
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
}
