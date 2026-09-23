<?php

namespace Database\Seeders\Demo\Catalogs;

use Database\Seeders\Demo\Support\BlogCategoryDefinition;
use Database\Seeders\Demo\Support\BlogPostDefinition;

final class AccountingBlogCatalog
{
    /**
     * @return list<BlogCategoryDefinition>
     */
    public function categories(): array
    {
        return [
            new BlogCategoryDefinition('tax-circulars', 'بخشنامه‌های مالیاتی', 'آخرین بخشنامه‌ها و تغییرات قوانین مالیاتی', 1),
            new BlogCategoryDefinition('accounting-guides', 'راهنمای حسابداری', 'آموزش‌های کاربردی حسابداری برای بازار کار', 2),
            new BlogCategoryDefinition('payroll', 'حقوق و دستمزد', 'محاسبات حقوق، بیمه و مالیات حقوق', 3),
            new BlogCategoryDefinition('vat', 'ارزش افزوده', 'اظهارنامه، دفاتر و نکات ارزش افزوده', 4),
        ];
    }

    /**
     * @return list<BlogPostDefinition>
     */
    public function posts(): array
    {
        return [
            new BlogPostDefinition(
                slug: 'demo-tax-year-1405-changes',
                title: 'تغییرات مهم قوانین مالیاتی ۱۴۰۵ برای مشاغل',
                excerpt: 'جمع‌بندی تغییرات نرخ‌ها، معافیت‌ها و تکالیف مؤدیان در سال جدید.',
                body: $this->articleBody(
                    'در سال ۱۴۰۵ چند تغییر کلیدی در تکالیف مالیاتی مشاغل اعمال شده است.',
                    ['بازبینی معافیت‌های پایه', 'زمان‌بندی ارسال اظهارنامه', 'نکات مهم برای اشخاص حقیقی']
                ),
                categorySlug: 'tax-circulars',
                views: 1280,
                daysAgo: 1,
            ),
            new BlogPostDefinition(
                slug: 'demo-withholding-tax-checklist',
                title: 'چک‌لیست مالیات تکلیفی برای حسابداران',
                excerpt: 'فهرست عملیاتی کسر و پرداخت مالیات‌های تکلیفی در شرکت‌ها.',
                body: $this->articleBody(
                    'مالیات تکلیفی یکی از پرتکرارترین خطاهای حسابداری در گزارش‌دهی است.',
                    ['شناسایی قراردادهای مشمول', 'محاسبه صحیح مبلغ کسر', 'ثبت و پرداخت در موعد']
                ),
                categorySlug: 'tax-circulars',
                views: 940,
                daysAgo: 3,
            ),
            new BlogPostDefinition(
                slug: 'demo-chart-of-accounts-sme',
                title: 'طراحی سرفصل حساب‌ها برای کسب‌وکارهای کوچک',
                excerpt: 'چطور ساختار حساب‌ها را ساده، قابل توسعه و استاندارد نگه دارید.',
                body: $this->articleBody(
                    'سرفصل حساب‌ها باید با اندازه کسب‌وکار و نیاز گزارش‌گیری هم‌خوان باشد.',
                    ['گروه‌بندی دارایی و بدهی', 'حساب‌های درآمد و هزینه', 'کدینگ قابل فهم برای تیم']
                ),
                categorySlug: 'accounting-guides',
                views: 2100,
                daysAgo: 4,
            ),
            new BlogPostDefinition(
                slug: 'demo-closing-entries-guide',
                title: 'راهنمای بستن حساب‌ها در پایان دوره مالی',
                excerpt: 'مراحل بستن حساب‌های موقت و انتقال نتیجه به سود و زیان انباشته.',
                body: $this->articleBody(
                    'بستن حساب‌ها اگر منظم انجام شود، صورت‌های مالی دقیق‌تری می‌سازد.',
                    ['بستن درآمدها و هزینه‌ها', 'ثبت خلاصه سود و زیان', 'کنترل تراز اختتامی']
                ),
                categorySlug: 'accounting-guides',
                views: 1560,
                daysAgo: 6,
            ),
            new BlogPostDefinition(
                slug: 'demo-inventory-methods',
                title: 'مقایسه روش‌های ارزیابی موجودی کالا',
                excerpt: 'FIFO، میانگین موزون و تاثیر انتخاب روش بر سود و مالیات.',
                body: $this->articleBody(
                    'انتخاب روش ارزیابی موجودی مستقیماً روی بهای تمام‌شده اثر می‌گذارد.',
                    ['مزایای FIFO در تورم', 'میانگین موزون برای کالاهای همگن', 'الزامات افشا در یادداشت‌ها']
                ),
                categorySlug: 'accounting-guides',
                views: 870,
                daysAgo: 8,
            ),
            new BlogPostDefinition(
                slug: 'demo-payroll-1405',
                title: 'محاسبه حقوق و دستمزد ۱۴۰۵؛ گام به گام',
                excerpt: 'از پایه حقوق تا بیمه، مالیات و خالص پرداختی.',
                body: $this->articleBody(
                    'محاسبه حقوق باید با آخرین بخشنامه‌ها و جداول مالیاتی هم‌راستا باشد.',
                    ['اجزای حکم حقوقی', 'کسور قانونی', 'خروجی لیست بیمه و مالیات']
                ),
                categorySlug: 'payroll',
                views: 3200,
                daysAgo: 2,
            ),
            new BlogPostDefinition(
                slug: 'demo-insurance-list-errors',
                title: 'خطاهای رایج در لیست بیمه تامین اجتماعی',
                excerpt: 'اشتباهاتی که باعث برگشت لیست و جریمه می‌شوند.',
                body: $this->articleBody(
                    'بیشتر خطاهای لیست بیمه از ناسازگاری کد شغلی و کارکرد ناشی می‌شود.',
                    ['تطبیق کارکرد با حضور', 'کنترل کد کارگاه', 'اصلاح لیست قبل از ارسال']
                ),
                categorySlug: 'payroll',
                views: 1120,
                daysAgo: 9,
            ),
            new BlogPostDefinition(
                slug: 'demo-payroll-tax-brackets',
                title: 'جدول مالیات حقوق و نکات اجرایی آن',
                excerpt: 'چطور پلکان مالیاتی را درست روی حکم کارمندان اعمال کنید.',
                body: $this->articleBody(
                    'اعمال نادرست پلکان مالیاتی باعث اختلاف با سامانه می‌شود.',
                    ['شناسایی درآمد مشمول', 'اعمال معافیت ماهانه', 'کنترل تجمعی سالانه']
                ),
                categorySlug: 'payroll',
                views: 760,
                daysAgo: 11,
            ),
            new BlogPostDefinition(
                slug: 'demo-vat-return-season',
                title: 'اظهارنامه ارزش افزوده فصلی؛ چک‌لیست ارسال',
                excerpt: 'قبل از ارسال اظهارنامه این موارد را حتماً بررسی کنید.',
                body: $this->articleBody(
                    'اظهارنامه ارزش افزوده نیاز به تطبیق خرید، فروش و اعتبار مالیاتی دارد.',
                    ['تطبیق فاکتورها با سامانه', 'کنترل اعتبار خرید', 'ثبت نهایی و دریافت رسید']
                ),
                categorySlug: 'vat',
                views: 1890,
                daysAgo: 5,
            ),
            new BlogPostDefinition(
                slug: 'demo-vat-credit-rules',
                title: 'قواعد پذیرش اعتبار مالیاتی ارزش افزوده',
                excerpt: 'کدام خریدها اعتبار می‌گیرند و کدام‌ها رد می‌شوند؟',
                body: $this->articleBody(
                    'پذیرش اعتبار مالیاتی وابسته به مستند بودن معامله و ثبت در سامانه است.',
                    ['فاکتور معتبر', 'ارتباط با فعالیت مؤدی', 'زمان‌بندی استفاده از اعتبار']
                ),
                categorySlug: 'vat',
                views: 990,
                daysAgo: 12,
            ),
            new BlogPostDefinition(
                slug: 'demo-vat-for-service-companies',
                title: 'ارزش افزوده در شرکت‌های خدماتی',
                excerpt: 'نکات خاص صدور صورت‌حساب و گزارش‌دهی برای خدمات.',
                body: $this->articleBody(
                    'شرکت‌های خدماتی معمولاً در تفکیک معاملات معاف و مشمول دچار ابهام می‌شوند.',
                    ['شناسایی خدمات مشمول', 'صدور صورت‌حساب استاندارد', 'تهیه گزارش فصلی']
                ),
                categorySlug: 'vat',
                views: 640,
                daysAgo: 14,
            ),
            new BlogPostDefinition(
                slug: 'demo-financial-statement-notes',
                title: 'یادداشت‌های توضیحی صورت‌های مالی؛ آنچه حسابدار باید بداند',
                excerpt: 'ساختار یادداشت‌ها و حداقل افشاهای ضروری برای شرکت‌ها.',
                body: $this->articleBody(
                    'یادداشت‌های توضیحی شفافیت صورت‌های مالی را برای استفاده‌کنندگان بالا می‌برد.',
                    ['رویه‌های حسابداری', 'افشای تعهدات', 'رویدادهای بعد از تاریخ ترازنامه']
                ),
                categorySlug: 'accounting-guides',
                views: 430,
                daysAgo: 16,
            ),
        ];
    }

    /**
     * @param  list<string>  $points
     */
    private function articleBody(string $intro, array $points): string
    {
        $items = collect($points)
            ->map(fn (string $point) => '<li>'.$point.'</li>')
            ->implode('');

        return <<<HTML
<p>{$intro}</p>
<p>در ادامه مهم‌ترین نکات عملی را مرور می‌کنیم:</p>
<ul>{$items}</ul>
<blockquote>این مطلب برای مرور سریع حسابداران و مدیران مالی تهیه شده و جایگزین مشاوره موردی نیست.</blockquote>
<p>اگر در اجرای این موارد به مشکل خوردید، می‌توانید از دوره‌های تخصصی راهبر حساب استفاده کنید.</p>
HTML;
    }
}
