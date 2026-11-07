<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsSetting;
use App\Services\Cms\CmsProductPresenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SiteDataService
{
    public function __construct(private CmsProductPresenter $productsPresenter) {}

    public function defaultProducts(): array
    {
        return $this->productsPresenter->defaultProducts();
    }

    public function products(): Collection
    {
        return $this->productsPresenter->products();
    }

    public function product(string $slug): ?array
    {
        return $this->productsPresenter->product($slug);
    }

    public function productImageUrl(?string $image): ?string
    {
        return $this->productsPresenter->productImageUrl($image);
    }

    public function navLinks(): array
    {
        $menuService = app(MenuService::class);
        $location = config('cms.menu_locations.primary', 'primary');
        $menuLinks = $menuService->linksForLocation($location);

        if (! empty($menuLinks)) {
            return $this->normalizeNavLinks($menuLinks);
        }

        $links = [
            ['route' => 'home', 'label' => 'صفحه اصلی'],
            [
                'route' => 'courses.index',
                'label' => 'دوره آموزش حسابداری',
                'children' => [
                    ['route' => 'courses.index', 'label' => 'همه دوره‌ها'],
                ],
            ],
            [
                'route' => 'blog.index',
                'label' => 'منابع حسابداری',
                'children' => [
                    ['route' => 'blog.index', 'label' => 'مقالات حسابداری'],
                ],
            ],
            [
                'route' => 'about',
                'label' => 'خدمات مشاوره مالیاتی',
                'children' => [
                    ['route' => 'about', 'label' => 'مشاوره مالیاتی'],
                    ['route' => 'about', 'label' => 'مشاوره حسابداری'],
                ],
            ],
            [
                'route' => 'courses.index',
                'label' => 'محصولات سامانه مودیان',
            ],
            [
                'route' => 'contact',
                'label' => 'ارتباط با ما',
                'children' => [
                    ['route' => 'contact', 'label' => 'تماس با ما'],
                    ['route' => 'about', 'label' => 'درباره ما'],
                ],
            ],
        ];

        $dynamic = CmsPage::query()
            ->published()
            ->where('show_in_nav', true)
            ->where('is_system', false)
            ->orderBy('sort_order')
            ->get();

        foreach ($dynamic as $page) {
            $links[] = [
                'route' => 'pages.show',
                'params' => ['slug' => $page->slug],
                'label' => $page->title,
            ];
        }

        return $links;
    }

    public function pageContent(string $slug, array $defaults = []): array
    {
        $page = CmsPage::query()->published()->where('slug', $slug)->first();

        if (! $page || empty($page->content)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $page->content);
    }

    public function dynamicPage(string $slug): ?CmsPage
    {
        return CmsPage::query()
            ->published()
            ->where('slug', $slug)
            ->where('is_system', false)
            ->first();
    }

    public function contact(): array
    {
        $mobile = CmsSetting::get('contact_mobile', '09333658333');

        return [
            'email' => CmsSetting::get('contact_email', config('cms.contact_email') ?: 'info@rahbarhesab.com'),
            'phone' => CmsSetting::get('contact_phone', config('cms.contact_phone') ?: '02191020105'),
            'phone_display' => CmsSetting::get('contact_phone_display', '۰۲۱-۹۱۰۲۰۱۰۵'),
            'mobile' => $mobile,
            'mobile_display' => CmsSetting::get('contact_mobile_display', '۰۹۳۳۳۶۵۸۳۳۳'),
            'address' => CmsSetting::get('contact_address', "تهران - خیابان توحید نبش کوچه\nصائب"),
            'hours' => CmsSetting::get('contact_hours', 'شنبه تا پنجشنبه ۹ الی ۲۱'),
            'map' => CmsSetting::get('contact_map', 'https://www.google.com/maps?q=35.7016614,51.3779503&hl=fa&z=17&output=embed'),
            'map_link' => 'https://maps.app.goo.gl/ps5hwVey1WyJ1ptU6',
            'instagram' => CmsSetting::get('social_instagram', config('cms.social.instagram')) ?: 'https://instagram.com/Rahbarhesab',
            'telegram' => CmsSetting::get('social_telegram', config('cms.social.telegram')) ?: 'https://t.me/rahbarhesab_MR',
            'whatsapp' => CmsSetting::get('social_whatsapp') ?: 'https://wa.me/989334653933',
            'youtube' => CmsSetting::get('social_youtube') ?: 'https://www.youtube.com/@OfficialRahbarhesab',
            'aparat' => CmsSetting::get('social_aparat') ?: 'https://www.aparat.com/Rahbarhesab',
            'bale' => CmsSetting::get('social_bale') ?: 'https://ble.ir/rahbarhesab',
            'rubika' => CmsSetting::get('social_rubika') ?: 'https://rubika.ir/rahbarhesab1',
            'linkedin' => CmsSetting::get('social_linkedin', config('cms.social.linkedin')) ?: 'https://www.linkedin.com/in/%D9%85%D9%88%D8%B3%D8%B3%D9%87-%D8%AE%D8%AF%D9%85%D8%A7%D8%AA%DB%8C-%D9%88-%D8%A2%D9%85%D9%88%D8%B2%D8%B4%DB%8C-%D9%85%D8%A7%D9%84%DB%8C-%D9%88-%D9%85%D8%A7%D9%84%DB%8C%D8%A7%D8%AA-%D8%B1%D9%87%D8%A8%D8%B1%D8%AD%D8%B3%D8%A7%D8%A8-034ba83a9/',
        ];
    }

    public function heroPills(): array
    {
        return [
            ['title' => 'راهبر CRM', 'text' => 'قیف فروش، پیگیری و گزارش — برای تیم‌های فروش', 'href' => 'courses.show', 'slug' => 'rahbar'],
            ['title' => 'نوژارو', 'text' => 'پلتفرم SaaS — مجموعه‌ها، ماژول و اشتراک', 'href' => 'courses.show', 'slug' => 'nojaro'],
            ['title' => 'ویلئو', 'text' => 'مدیریت مالی شخصی — حساب‌ها، بودجه و دارایی', 'href' => 'courses.show', 'slug' => 'vileo'],
            ['title' => 'افزونه وردپرس', 'text' => 'اتصال سایت و ووکامرس به CRM — خودکار', 'href' => 'courses.show', 'slug' => 'wordpress-plugin'],
        ];
    }

    public function devCapabilities(): array
    {
        return [
            [
                'title' => 'معماری مقیاس‌پذیر',
                'desc' => 'سیستم‌هایی که با رشد کسب‌وکار شما بزرگ می‌شوند — نه اینکه دوباره از صفر بسازید.',
                'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
            ],
            [
                'title' => 'API و یکپارچه‌سازی',
                'desc' => 'اتصال CRM، سایت، فروشگاه و ابزارهای ثالث — بدون کار دستی و بدون خطا.',
                'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
            ],
            [
                'title' => 'کد تمیز و قابل نگهداری',
                'desc' => 'استانداردهای مهندسی نرم‌افزار — تست، مستندسازی و تحویل منظم.',
                'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
            ],
            [
                'title' => 'تحویل سریع MVP',
                'desc' => 'نسخه اولیه در چند هفته — برای تست بازار یا جذب سرمایه.',
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
            ],
        ];
    }

    public function devStats(): array
    {
        return [
            ['value' => '+۱۵', 'label' => 'توسعه‌دهنده'],
            ['value' => '+۵۰', 'label' => 'پروژه تحویل‌شده'],
            ['value' => '۹۹.۹٪', 'label' => 'آپتایم محصولات'],
            ['value' => '< ۴۸h', 'label' => 'پاسخ فنی'],
        ];
    }

    public function techStack(): array
    {
        return [
            ['name' => 'Laravel', 'color' => 'orange'],
            ['name' => 'PHP 8', 'color' => 'purple'],
            ['name' => 'Vue.js', 'color' => 'blue'],
            ['name' => 'React', 'color' => 'blue'],
            ['name' => 'MySQL', 'color' => 'orange'],
            ['name' => 'Redis', 'color' => 'purple'],
            ['name' => 'Docker', 'color' => 'blue'],
            ['name' => 'REST API', 'color' => 'orange'],
            ['name' => 'WordPress', 'color' => 'blue'],
            ['name' => 'Tailwind CSS', 'color' => 'purple'],
            ['name' => 'Alpine.js', 'color' => 'orange'],
            ['name' => 'CI/CD', 'color' => 'blue'],
        ];
    }

    public function services(): array
    {
        return [
            [
                'title' => 'نرم‌افزار مخصوص شما',
                'desc' => 'وقتی محصول آماده جواب نمی‌دهد، دقیقاً همان چیزی را می‌سازیم که کسب‌وکارتان نیاز دارد.',
                'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
            ],
            [
                'title' => 'وصل کردن سیستم‌ها',
                'desc' => 'سایت، فروشگاه و CRM شما با هم حرف بزنند — بدون کار دستی و بدون خطا.',
                'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
            ],
            [
                'title' => 'نسخه اولیه سریع',
                'desc' => 'ایده‌تان را زود به محصول تبدیل کنید — برای تست بازار یا جذب سرمایه.',
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
            ],
            [
                'title' => 'تیم برنامه‌نویس اختصاصی',
                'desc' => 'یک تیم کنار شما می‌ماند — توسعه، به‌روزرسانی و پشتیبانی مداوم.',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            ],
        ];
    }

    public function whyBisan(): array
    {
        return [
            [
                'tag' => '۰۱ · سادگی',
                'title' => 'یک تیم، یک پاسخ',
                'desc' => 'محصول آماده می‌خواهید یا پروژه اختصاصی — شما نتیجه می‌گیرید، نه دردسر فنی.',
                'accent' => 'orange',
                'visual' => 'ecosystem',
                'size' => 'lg',
            ],
            [
                'tag' => '۰۲ · سرعت',
                'title' => 'همین هفته شروع کنید',
                'desc' => 'بدون سرور و نصب پیچیده. تیم‌تان آموزش می‌گیرد و کار را شروع می‌کند.',
                'accent' => 'blue',
                'visual' => 'speed',
                'size' => 'sm',
            ],
            [
                'tag' => '۰۳ · شفافیت',
                'title' => 'ببینید چه خبر است',
                'desc' => 'فروش، عملکرد تیم و درآمد — گزارش‌هایی که واقعاً به تصمیم‌گیری کمک می‌کنند.',
                'accent' => 'purple',
                'visual' => 'analytics',
                'size' => 'md',
            ],
            [
                'tag' => '۰۴ · اطمینان',
                'title' => 'کنار شما می‌مانیم',
                'desc' => 'از اولین تماس تا بعد از تحویل — پشتیبانی فارسی، واقعی و در دسترس.',
                'accent' => 'gradient',
                'visual' => 'support',
                'size' => 'wide',
            ],
        ];
    }

    public function aboutTimeline(): array
    {
        return [
            ['year' => '۱۳۹۵', 'title' => 'شروع فعالیت', 'desc' => 'آغاز آموزش تخصصی حسابداری و مالیات'],
            ['year' => '۱۴۰۰', 'title' => 'گسترش دوره‌ها', 'desc' => 'راه‌اندازی دوره‌های آنلاین و حضوری'],
            ['year' => '۱۴۰۴', 'title' => 'راهبر حساب', 'desc' => 'پلتفرم آموزشی و خدمات مالی یکپارچه'],
        ];
    }

    public function aboutPillars(): array
    {
        return [
            ['icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'text' => 'آموزش کاربردی ویژه بازار کار'],
            ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'مدرک معتبر فنی‌حرفه‌ای و CIP'],
            ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'text' => 'تیم مشاوران مالی و مالیاتی'],
            ['icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'پشتیبانی تخصصی ۷/۲۴'],
        ];
    }

    public function stats(): array
    {
        return [
            ['value' => '+۲۰', 'label' => 'دوره آموزشی', 'hint' => 'کاربردی و تخصصی', 'icon' => 'course'],
            ['value' => '+۱۰۰', 'label' => 'مشاور', 'hint' => 'خدمات مالی و مالیاتی', 'icon' => 'team'],
            ['value' => '+۵۰۰۰', 'label' => 'دانشجو', 'hint' => 'در سراسر ایران', 'icon' => 'users'],
            ['value' => '۷/۲۴', 'label' => 'پشتیبانی', 'hint' => 'پاسخگویی تخصصی', 'icon' => 'support'],
        ];
    }

    public function partners(): array
    {
        return ['سازمان فنی‌حرفه‌ای', 'CIP', 'اتاق بازرگانی', 'سازمان امور مالیاتی'];
    }

    public function serviceProcess(): array
    {
        return [
            ['step' => '۱', 'title' => 'شنیدن نیاز شما', 'desc' => 'در یک جلسه کوتاه، دقیقاً می‌فهمیم چه مشکلی دارید و چه نتیجه‌ای می‌خواهید.'],
            ['step' => '۲', 'title' => 'طراحی راه‌حل', 'desc' => 'معماری، زمان‌بندی و هزینه را شفاف ارائه می‌دهیم — بدون اصطلاحات پیچیده.'],
            ['step' => '۳', 'title' => 'ساخت و تحویل', 'desc' => 'نسخه اولیه سریع، بازخورد شما، و تکرار تا رسیدن به محصول نهایی.'],
            ['step' => '۴', 'title' => 'پشتیبانی مداوم', 'desc' => 'بعد از تحویل هم کنار شما می‌مانیم — به‌روزرسانی، رفع باگ و توسعه.'],
        ];
    }

    public function serviceIndustries(): array
    {
        return [
            'کلینیک و سلامت',
            'آموزش و مشاوره',
            'تولید و توزیع',
            'املاک و ساختمان',
            'خدمات مالی',
            'فروشگاه آنلاین',
        ];
    }

    public function productComparison(): array
    {
        return [
            ['feature' => 'مدیریت مشتری و فروش', 'rahbar' => true, 'nojaro' => false, 'vileo' => false, 'wordpress' => false],
            ['feature' => 'قیف فروش و گزارش تیم', 'rahbar' => true, 'nojaro' => false, 'vileo' => false, 'wordpress' => false],
            ['feature' => 'پلتفرم SaaS و اشتراک', 'rahbar' => false, 'nojaro' => true, 'vileo' => false, 'wordpress' => false],
            ['feature' => 'مدیریت مجموعه و ماژول', 'rahbar' => false, 'nojaro' => true, 'vileo' => false, 'wordpress' => false],
            ['feature' => 'اتصال سایت به CRM', 'rahbar' => false, 'nojaro' => false, 'vileo' => false, 'wordpress' => true],
            ['feature' => 'مدیریت مالی شخصی', 'rahbar' => false, 'nojaro' => false, 'vileo' => true, 'wordpress' => false],
            ['feature' => 'ثبت هوشمند از پیامک بانکی', 'rahbar' => false, 'nojaro' => false, 'vileo' => true, 'wordpress' => false],
            ['feature' => 'گزارش و داشبورد', 'rahbar' => true, 'nojaro' => true, 'vileo' => true, 'wordpress' => false],
            ['feature' => 'پشتیبانی فارسی', 'rahbar' => true, 'nojaro' => true, 'vileo' => true, 'wordpress' => true],
        ];
    }

    public function contactFaq(): array
    {
        return [
            ['q' => 'مشاوره رایگان است؟', 'a' => 'بله. اولین مشاوره آموزشی و مالی رایگان است.'],
            ['q' => 'دوره‌ها به چه صورت برگزار می‌شوند؟', 'a' => 'دوره‌ها به‌صورت آنلاین و با پشتیبانی تخصصی ارائه می‌شوند.'],
            ['q' => 'مدرک دوره‌ها معتبر است؟', 'a' => 'بله، مدارک فنی‌حرفه‌ای و CIP ارائه می‌شود.'],
            ['q' => 'چطور ثبت‌نام کنم؟', 'a' => 'از صفحه دوره‌ها، دوره موردنظر را انتخاب و ثبت‌نام کنید.'],
        ];
    }

    public function aboutMission(): array
    {
        return [
            'mission' => 'آموزش حسابداری و مالیات به‌صورت کاربردی برای ورود سریع‌تر به بازار کار.',
            'vision' => 'تربیت رهبران حسابداری و ارائه خدمات مالی معتبر در سراسر ایران.',
        ];
    }

    public function rahbarPageMeta(): array
    {
        return [
            // Titles/descriptions mirror the live WordPress (Rank Math) output.
            'home' => [
                'title' => 'موسسه آموزش حسابداری راهبر حساب و اجرای خدمات مالی و مالیاتی',
                'description' => 'موسسه آموزش حسابداری راهبر حساب ارائه دهنده آموزش، خدمات اجرایی و نرم افزار های مالی و مالیاتی',
                'keywords' => 'راهبر حساب, آموزش حسابداری, مالیات, اظهارنامه, حسابداری, rahbarhesab',
                'og_title' => 'راهبر حساب — خالق رهبران حسابداری',
            ],
            'about' => [
                'title' => 'درباره ما - راهبر حساب',
                'description' => 'ما را بهتر بشناسید',
                'keywords' => 'درباره راهبر حساب, آموزش حسابداری, موسسه حسابداری',
            ],
            'contact' => [
                'title' => 'تماس با ما - راهبر حساب',
                'description' => 'ارتباط با راهبر حساب، در سریع‌ترین زمان پاسخگوی شما هستیم. راهنمای استفاده از محصولات خریداری شده در صورتی که محصولات آموزشی خریده اید و در مورد شروع و نحوه',
                'keywords' => 'تماس راهبر حساب, مشاوره حسابداری, پشتیبانی',
            ],
        ];
    }

    public function pageMeta(string $slug, ?CmsPage $page = null, bool $loaded = false): array
    {
        $defaults = $this->defaultPageMeta()[$slug] ?? [];
        if (! $loaded) {
            $page = CmsPage::query()->published()->where('slug', $slug)->first();
        }

        if (! $page) {
            return $defaults;
        }

        return array_filter([
            'title' => $page->meta_title ?: $page->title,
            'description' => $page->meta_description ?: ($defaults['description'] ?? null),
            'keywords' => $page->meta_keywords ?: ($defaults['keywords'] ?? null),
            'og_title' => $page->meta_title ?: $page->title,
            'og_image' => $page->og_image,
            'canonical' => $page->canonical_url,
            'robots' => $page->robots ?: ($defaults['robots'] ?? 'index, follow'),
        ]);
    }

    public function defaultPageMeta(): array
    {
        return $this->rahbarPageMeta();
    }

    public function sharedViewData(): array
    {
        $cartCount = 0;

        try {
            $cartCount = app(CartService::class)->quantityTotal();
        } catch (\Throwable) {
            // Cart tables may not exist during install.
        }

        return [
            'navLinks' => $this->navLinks(),
            'contact' => $this->contact(),
            'cartCount' => $cartCount,
            'sitePopup' => app(PopupService::class)->current(),
        ];
    }

    private function normalizeNavLinks(array $items): array
    {
        return collect($items)->map(function (array $item) {
            $children = $this->normalizeNavLinks($item['children'] ?? []);

            $link = [
                'label' => $item['label'] ?? '',
                'href' => $item['href'] ?? $item['url'] ?? null,
                'target' => $item['target'] ?? '_self',
            ];

            if (isset($item['route'])) {
                $link['route'] = $item['route'];
                $link['params'] = $item['params'] ?? [];
            }

            if ($children !== []) {
                $link['children'] = $children;
            }

            return $link;
        })->values()->all();
    }

    public function clearCache(): void
    {
        Cache::forget('cms_products');
        Cache::forget('cms_home_content');
    }
}
