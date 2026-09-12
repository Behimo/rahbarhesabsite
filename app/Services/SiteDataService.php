<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsProduct;
use App\Models\CmsSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteDataService
{
    public function navLinks(): array
    {
        $menuService = app(\App\Services\MenuService::class);
        $location = config('cms.menu_locations.primary', 'primary');
        $menuLinks = $menuService->linksForLocation($location);

        if (!empty($menuLinks)) {
            $links = collect($menuLinks)->map(function (array $item) {
                if (isset($item['href'])) {
                    return ['href' => $item['href'], 'label' => $item['label']];
                }

                return $item;
            })->all();

            return \App\Support\Hook::applyFilters('cms.nav.links', $links);
        }

        $links = [
            ['route' => 'home', 'label' => 'خانه'],
            ['route' => 'courses.index', 'label' => 'دوره‌ها'],
            ['route' => 'blog.index', 'label' => 'بلاگ و اخبار'],
            ['route' => 'about', 'label' => 'درباره ما'],
            ['route' => 'contact', 'label' => 'تماس'],
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

        return \App\Support\Hook::applyFilters('cms.nav.links', $links);
    }

    public function pageContent(string $slug, array $defaults = []): array
    {
        $page = CmsPage::query()->published()->where('slug', $slug)->first();

        if (!$page || empty($page->content)) {
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
        return [
            'email' => CmsSetting::get('contact_email', config('cms.contact_email')),
            'phone' => CmsSetting::get('contact_phone', config('cms.contact_phone')),
        ];
    }

    public function heroPills(): array
    {
        return [
            ['title' => 'راهبر CRM', 'text' => 'قیف فروش، پیگیری و گزارش — برای تیم‌های فروش', 'href' => 'products.show', 'slug' => 'rahbar'],
            ['title' => 'نوژارو', 'text' => 'پلتفرم SaaS — مجموعه‌ها، ماژول و اشتراک', 'href' => 'products.show', 'slug' => 'nojaro'],
            ['title' => 'ویلئو', 'text' => 'مدیریت مالی شخصی — حساب‌ها، بودجه و دارایی', 'href' => 'products.show', 'slug' => 'vileo'],
            ['title' => 'افزونه وردپرس', 'text' => 'اتصال سایت و ووکامرس به CRM — خودکار', 'href' => 'products.show', 'slug' => 'wordpress-plugin'],
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

    public function defaultProducts(): array
    {
        return [
            [
                'slug' => 'rahbar',
                'title' => 'راهبر',
                'subtitle' => 'Rahbar CRM',
                'description' => 'مشتری را گم نکنید. از اولین تماس تا امضای قرارداد، همه‌چیز یکجا — با قیف فروش، گزارش لحظه‌ای و تیم هماهنگ.',
                'accent' => 'orange',
                'visual' => 'crm',
                'audience' => 'تیم فروش، بازاریابی و مدیران کسب‌وکار',
                'features' => [
                    'قیف فروش بصری — ببینید معامله کجا گیر کرده',
                    'یادآوری خودکار — هیچ پیگیری فراموش نمی‌شود',
                    'گزارش عملکرد هر فروشنده، بدون اکسل',
                    'مدیریت لید، مخاطب، معامله و فعالیت‌ها',
                    'گفتگوی تیم و اعلان‌های لحظه‌ای',
                    'داشبورد درآمد و نرخ تبدیل',
                ],
                'cta' => 'دمو رایگان راهبر',
                'is_featured' => true,
                'dashboard_image' => '/marketing/rahbar-dashboard.png',
                'website_url' => 'https://rahbarteam.com',
                'body' => "راهبر CRM سیستم مدیریت مشتری و فروش بیسان است — ساخته‌شده برای تیم‌های فروش ایرانی که می‌خواهند سریع‌تر بفروشند و منظم‌تر کار کنند.\n\nاز اولین لید تا امضای قرارداد، هر مرحله در قیف فروش قابل مشاهده است. تسک‌های روزانه، پیگیری‌های سررسیدگذشته، گزارش درآمد و عملکرد فروشندگان — همه در یک پنل فارسی و ساده.\n\nاگر الان با اکسل، واتساپ یا دفترچه یادداشت مشتریان را مدیریت می‌کنید، راهبر جایگزین حرفه‌ای و مقیاس‌پذیر است. بیش از ۱۲۰۰ کسب‌وکار هر روز با راهبر کار می‌کنند.",
                'highlights' => [
                    ['label' => 'قیف فروش', 'desc' => 'مراحل فروش را ببینید و گلوگاه‌ها را پیدا کنید'],
                    ['label' => 'یادآوری خودکار', 'desc' => 'هیچ پیگیری فراموش نمی‌شود'],
                    ['label' => 'گزارش تیم', 'desc' => 'عملکرد هر فروشنده شفاف و قابل مقایسه'],
                ],
                'stats' => [
                    ['value' => '+۱۲۰۰', 'label' => 'کسب‌وکار فعال'],
                    ['value' => '۸۹٪', 'label' => 'نرخ تبدیل میانگین'],
                    ['value' => '۴۰٪', 'label' => 'سریع‌تر تا قرارداد'],
                    ['value' => '۳ روز', 'label' => 'راه‌اندازی اولیه'],
                ],
                'capabilities' => [
                    ['title' => 'قیف فروش', 'desc' => 'معاملات را در مراحل مختلف ببینید، جابه‌جا کنید و گلوگاه‌های فروش را شناسایی کنید.'],
                    ['title' => 'لید و مخاطب', 'desc' => 'همه اطلاعات تماس، تاریخچه مکالمه و وضعیت هر مشتری در یک پروفایل واحد.'],
                    ['title' => 'وظایف و پیگیری', 'desc' => 'تسک روزانه، سررسید گذشته و یادآوری خودکار — تیم همیشه می‌داند قدم بعدی چیست.'],
                    ['title' => 'بازاریابی و کمپین', 'desc' => 'لیدهای کمپین را ردیابی کنید و ببینید کدام کانال بهترین نتیجه می‌دهد.'],
                    ['title' => 'گزارش و تحلیل', 'desc' => 'درآمد، نرخ تبدیل، عملکرد فروشندگان و قیف فروش — بدون خروجی گرفتن از اکسل.'],
                    ['title' => 'گفتگوی تیم', 'desc' => 'هماهنگی داخلی بدون پراکندگی پیام‌ها در واتساپ و تلگرام.'],
                ],
                'use_cases' => [
                    ['title' => 'تیم فروش B2B', 'desc' => 'پیگیری قراردادهای بلندمدت و مذاکرات چندمرحله‌ای'],
                    ['title' => 'آژانس و خدمات', 'desc' => 'مدیریت پروژه‌های مشتری و تحویل به‌موقع'],
                    ['title' => 'فروشگاه و توزیع', 'desc' => 'پیگیری سفارش‌های عمده و مشتریان تکراری'],
                    ['title' => 'استارتاپ در حال رشد', 'desc' => 'ساختار فروش از روز اول، قبل از شلوغ شدن'],
                ],
                'benefits' => [
                    ['pain' => 'پیگیری‌ها در واتساپ و اکسل گم می‌شود', 'solution' => 'همه تماس‌ها و وظایف در یک سیستم با یادآور خودکار'],
                    ['pain' => 'نمی‌دانید کدام فروشنده بهتر کار می‌کند', 'solution' => 'گزارش عملکرد شفاف برای هر نفر در تیم'],
                    ['pain' => 'مدیر نمی‌داند معامله کجا گیر کرده', 'solution' => 'قیف فروش بصری با وضعیت لحظه‌ای هر معامله'],
                    ['pain' => 'گزارش‌گیری ساعت‌ها وقت می‌گیرد', 'solution' => 'داشبورد آماده — درآمد، لید و تبدیل در یک نگاه'],
                ],
                'faqs' => [
                    ['q' => 'راهبر برای چند نفر مناسب است؟', 'a' => 'از تیم ۲ نفره تا سازمان‌های ۵۰+ نفره — با نقش‌های دسترسی مختلف و مدیریت مجموعه.'],
                    ['q' => 'چقدر طول می‌کشد راه‌اندازی شود؟', 'a' => 'معمولاً در ۳ روز کاری آماده استفاده است. تیم ما آموزش رایگان هم ارائه می‌دهد.'],
                    ['q' => 'آیا با سایت وردپرس وصل می‌شود؟', 'a' => 'بله — با افزونه وردپرس بیسان، لیدهای سایت مستقیم وارد راهبر می‌شوند.'],
                    ['q' => 'داده‌ها کجا ذخیره می‌شوند؟', 'a' => 'روی سرور امن ابری با پشتیبان‌گیری منظم. دسترسی ۲۴ ساعته از هر دستگاه.'],
                ],
                'testimonial' => [
                    'quote' => 'بعد از راهبر، تیم فروش ما ۴۰٪ سریع‌تر به قرارداد می‌رسد. دیگر هیچ پیگیری در اکسل گم نمی‌شود و مدیر هر روز وضعیت قیف را می‌بیند.',
                    'name' => 'سارا محمدی',
                    'role' => 'مدیر فروش، شرکت توزیع کالا',
                ],
            ],
            [
                'slug' => 'nojaro',
                'title' => 'نوژارو',
                'subtitle' => 'Nozharo',
                'description' => 'پلتفرم SaaS آماده — مجموعه‌ها، ماژول‌ها، اشتراک و پرداخت را از یک پنل مدیریت کنید و سریع‌تر رشد کنید.',
                'accent' => 'purple',
                'visual' => 'service',
                'audience' => 'استارتاپ‌های SaaS، پلتفرم‌ها و کسب‌وکارهای چندمحصولی',
                'features' => [
                    'مدیریت چندمجموعه‌ای از یک داشبورد',
                    'کاتالوگ ماژول و اشتراک‌های فعال',
                    'پرداخت SaaS و گزارش تراکنش‌ها',
                    'تیم فروش، پورسانت و دوره حقوق',
                    'پشتیبانی تیکت و مدیریت کاربران',
                    'هشدار انقضای اشتراک و مجموعه‌ها',
                ],
                'cta' => 'دمو رایگان نوژارو',
                'is_featured' => false,
                'dashboard_image' => '/marketing/nozharo-dashboard.png',
                'website_url' => 'https://nozharo.ir',
                'body' => "نوژارو زیرساخت SaaS بیسان است — برای کسب‌وکارهایی که چند محصول، چند مشتری سازمانی یا چند شعبه دارند و نمی‌خواهند از صفر پلتفرم بسازند.\n\nدر پنل مدیریت نوژارو، مجموعه‌ها، کاربران، ماژول‌های فعال، اشتراک‌ها، پرداخت‌ها و تیم فروش را یکجا می‌بینید. هر مجموعه ماژول‌های خود را فعال می‌کند و شما کل اکوسیستم را کنترل می‌کنید.\n\nبه‌جای ماه‌ها توسعه زیرساخت، نوژارو را در چند هفته راه‌اندازی کنید و روی رشد محصول تمرکز کنید.",
                'highlights' => [
                    ['label' => 'مدیریت مجموعه‌ها', 'desc' => 'کاربران، پرسنل و وضعیت هر مجموعه'],
                    ['label' => 'ماژول و اشتراک', 'desc' => 'کاتالوگ ماژول‌ها و اشتراک‌های فعال'],
                    ['label' => 'مالی و پرداخت', 'desc' => 'تراکنش‌ها، درآمد و گزارش مالی'],
                ],
                'stats' => [
                    ['value' => '۱۲+', 'label' => 'ماژول آماده'],
                    ['value' => 'چند', 'label' => 'مجموعه همزمان'],
                    ['value' => '۱ پنل', 'label' => 'مدیریت کل اکوسیستم'],
                    ['value' => '۲ هفته', 'label' => 'تا راه‌اندازی'],
                ],
                'capabilities' => [
                    ['title' => 'مدیریت مجموعه‌ها', 'desc' => 'ایجاد، ویرایش و نظارت بر مجموعه‌ها — با هشدار انقضا و وضعیت فعال/غیرفعال.'],
                    ['title' => 'کاربران و پرسنل', 'desc' => 'مدیریت دسترسی کاربران، پرسنل پلتفرم و نقش‌های سازمانی.'],
                    ['title' => 'کاتالوگ ماژول', 'desc' => 'تعریف ماژول‌ها، قیمت‌گذاری و فعال‌سازی اشتراک برای هر مجموعه.'],
                    ['title' => 'پرداخت و مالی', 'desc' => 'تراکنش‌های SaaS، درآمد کل و گزارش پرداخت‌های موفق.'],
                    ['title' => 'تیم فروش', 'desc' => 'نرخ پورسانت، دوره‌های حقوق و گزارش عملکرد فروشندگان.'],
                    ['title' => 'پشتیبانی', 'desc' => 'سیستم تیکت یکپارچه برای پاسخگویی به مشتریان مجموعه‌ها.'],
                ],
                'use_cases' => [
                    ['title' => 'استارتاپ SaaS', 'desc' => 'راه‌اندازی سریع بدون ساخت زیرساخت از صفر'],
                    ['title' => 'پلتفرم چندمحصولی', 'desc' => 'هر محصول به‌صورت ماژول جداگانه فعال می‌شود'],
                    ['title' => 'نمایندگی و فروش', 'desc' => 'مدیریت تیم فروش، پورسانت و مشتریان سازمانی'],
                    ['title' => 'اکوسیستم سازمانی', 'desc' => 'چند واحد یا شعبه با داشبورد مرکزی'],
                ],
                'benefits' => [
                    ['pain' => 'ساخت پلتفرم SaaS ماه‌ها زمان می‌برد', 'solution' => 'زیرساخت آماده — تمرکز روی محصول، نه کدنویسی پایه'],
                    ['pain' => 'مدیریت اشتراک و پرداخت پراکنده است', 'solution' => 'همه تراکنش‌ها و اشتراک‌ها در یک پنل مالی'],
                    ['pain' => 'هر مشتری سازمانی جداگانه پیگیری می‌شود', 'solution' => 'مجموعه‌ها با ماژول و اشتراک اختصاصی'],
                    ['pain' => 'گزارش درآمد و فروش شفاف نیست', 'solution' => 'داشبورد درآمد، خریدهای موفق و گزارش پورسانت'],
                ],
                'faqs' => [
                    ['q' => 'نوژارو برای چه کسب‌وکارهایی مناسب است؟', 'a' => 'استارتاپ‌های SaaS، پلتفرم‌های نرم‌افزاری، آژانس‌هایی با چند محصول و هر کسب‌وکاری که چند مشتری سازمانی دارد.'],
                    ['q' => 'آیا می‌توان ماژول جدید اضافه کرد؟', 'a' => 'بله — کاتالوگ ماژول‌ها قابل توسعه است و هر مجموعه ماژول‌های موردنیاز خود را فعال می‌کند.'],
                    ['q' => 'پرداخت آنلاین پشتیبانی می‌شود؟', 'a' => 'بله — تراکنش‌های SaaS، پرداخت‌های موفق و گزارش مالی در پنل مدیریت قابل مشاهده است.'],
                    ['q' => 'چقدر طول می‌کشد راه‌اندازی شود؟', 'a' => 'بسته به تعداد ماژول‌ها، معمولاً بین ۱ تا ۲ هفته تا آماده‌سازی اولیه.'],
                ],
                'testimonial' => [
                    'quote' => 'نوژارو مدیریت مجموعه‌ها، اشتراک‌ها و پرداخت‌ها را یکجا حل کرد. دیگر نیازی به چند ابزار جدا نیست — همه‌چیز از یک داشبورد کنترل می‌شود.',
                    'name' => 'امیر کریمی',
                    'role' => 'مدیر محصول، استارتاپ SaaS',
                ],
            ],
            [
                'slug' => 'vileo',
                'title' => 'ویلئو',
                'subtitle' => 'Vileo',
                'description' => 'مدیریت مالی شخصی — همه حساب‌ها، بودجه، دارایی و اقساط در یک اپ موبایل‌محور با ثبت هوشمند از پیامک بانکی.',
                'accent' => 'green',
                'visual' => 'finance',
                'audience' => 'افراد و خانواده‌هایی که می‌خواهند کنترل مالی شخصی داشته باشند',
                'features' => [
                    'داشبورد یکپارچه — موجودی، دارایی و خالص دارایی',
                    'ثبت هوشمند تراکنش از پیامک بانکی (اندروید)',
                    'بودجه ماهانه، دسته‌بندی و انتقال بین حساب‌ها',
                    'پیگیری طلا، دلار، بیت‌کوین و ارزش‌گذاری دارایی',
                    'اقساط، چک و یادآوری سررسید',
                    'سلامت مالی، اهداف پس‌انداز و سناریوهای بازار',
                ],
                'cta' => 'شروع با ویلئو',
                'is_featured' => false,
                'website_url' => 'https://batiro.ir',
                'body' => "ویلئو (Vileo) وب‌اپلیکیشن و اپ اندروید مدیریت مالی شخصی بیسان است — ساخته‌شده برای کاربران فارسی‌زبان و بازار ایران.\n\nبا ویلئو همه حساب‌های بانکی و نقدی خود را یک‌جا می‌بینید، هزینه و درآمد ثبت می‌کنید، بودجه ماهانه تنظیم می‌کنید و خالص دارایی‌تان — شامل طلا، سکه و سایر دارایی‌ها — را لحظه‌به‌لحظه پیگیری می‌کنید.\n\nویژگی متمایز ویلئو، ثبت هوشمند تراکنش از طریق خواندن پیامک‌های بانکی در نسخه اندروید است: تراکنش‌ها پیشنهاد می‌شوند و فقط با تأیید شما ثبت می‌شوند.\n\nفراتر از حسابداری روزمره، ویلئو ابزارهایی مثل امتیاز سلامت مالی، اهداف پس‌انداز، شبیه‌سازی سناریوهای بازار، «داستان ثروت» و فضاهای مالی مشترک برای خانواده و گروه‌ها ارائه می‌دهد.",
                'highlights' => [
                    ['label' => 'همه حساب‌ها یک‌جا', 'desc' => 'موجودی، درآمد، هزینه و بودجه ماهانه'],
                    ['label' => 'ثبت از پیامک', 'desc' => 'تراکنش بانکی پیشنهاد می‌شود — تأیید با یک لمس'],
                    ['label' => 'رشد ثروت', 'desc' => 'دارایی، اهداف، سناریو و سلامت مالی'],
                ],
                'stats' => [
                    ['value' => '۱ اپ', 'label' => 'وب + اندروید'],
                    ['value' => 'SMS', 'label' => 'ثبت هوشمند بانکی'],
                    ['value' => 'شمسی', 'label' => 'تقویم و تومان'],
                    ['value' => 'OTP', 'label' => 'ورود امن با موبایل'],
                ],
                'capabilities' => [
                    ['title' => 'داشبورد مالی', 'desc' => 'موجودی حساب‌ها، ارزش دارایی‌ها، خالص دارایی و نمودار درآمد/هزینه در یک نگاه.'],
                    ['title' => 'ثبت هوشمند', 'desc' => 'خواندن پیامک بانکی در اندروید — تراکنش پیشنهادی با تأیید کاربر.'],
                    ['title' => 'حساب و بودجه', 'desc' => 'چند حساب، دسته‌بندی، بودجه ماهانه و انتقال بین حساب‌ها.'],
                    ['title' => 'دارایی و بازار', 'desc' => 'طلا، نقره، دلار، بیت‌کوین — ارزش‌گذاری لحظه‌ای از بازار.'],
                    ['title' => 'اقساط و چک', 'desc' => 'برنامه پرداخت، سررسید، یادآوری و پیگیری وصول.'],
                    ['title' => 'فضاهای مشترک', 'desc' => 'مدیریت مالی خانواده، سفر، پروژه و گروه — با تسویه و چت.'],
                ],
                'use_cases' => [
                    ['title' => 'کنترل هزینه روزمره', 'desc' => 'ثبت سریع هزینه و درآمد با بودجه ماهانه'],
                    ['title' => 'مدیریت اقساط', 'desc' => 'پیگیری وام، قسط و چک با یادآوری سررسید'],
                    ['title' => 'پیگیری دارایی', 'desc' => 'طلا، سکه، املاک و ارز — ارزش لحظه‌ای'],
                    ['title' => 'مالی مشترک', 'desc' => 'هزینه‌های خانواده، سفر یا پروژه با اعضای گروه'],
                ],
                'benefits' => [
                    ['pain' => 'تراکنش‌ها در پیامک و دفترچه پراکنده است', 'solution' => 'ثبت هوشمند از SMS بانکی با تأیید یک‌لمسی'],
                    ['pain' => 'نمی‌دانید واقعاً چقدر دارید', 'solution' => 'خالص دارایی = حساب‌ها + دارایی‌ها در یک داشبورد'],
                    ['pain' => 'بودجه ماهانه رعایت نمی‌شود', 'solution' => 'دسته‌بندی و پیشرفت بودجه با هشدار'],
                    ['pain' => 'برنامه‌ریزی مالی سخت است', 'solution' => 'اهداف، سناریو و امتیاز سلامت مالی'],
                ],
                'faqs' => [
                    ['q' => 'ویلئو برای چه کسانی مناسب است؟', 'a' => 'هر فرد یا خانواده‌ای که می‌خواهد هزینه، درآمد، دارایی و اقساط خود را یک‌جا مدیریت کند.'],
                    ['q' => 'ثبت از پیامک بانکی چطور کار می‌کند؟', 'a' => 'در نسخه اندروید، پیامک‌های بانکی خوانده می‌شوند و تراکنش پیشنهادی نمایش داده می‌شود — فقط با تأیید شما ثبت می‌شود.'],
                    ['q' => 'آیا اپ اندروید دارد؟', 'a' => 'بله — علاوه بر وب‌اپ، نسخه اندروید با قابلیت import پیامک و به‌روزرسانی درون‌برنامه‌ای.'],
                    ['q' => 'اطلاعات مالی امن است؟', 'a' => 'ورود با OTP موبایل — داده‌های مالی محرمانه و روی سرور امن ذخیره می‌شوند.'],
                ],
                'testimonial' => [
                    'quote' => 'با ویلئو دیگر پیامک‌های بانکی را یکی‌یکی دستی وارد نمی‌کنم. همه حساب‌ها و بودجه ماهانه‌ام یک‌جاست و می‌دانم پولم کجا می‌رود.',
                    'name' => 'مریم احمدی',
                    'role' => 'کاربر ویلئو',
                ],
            ],
            [
                'slug' => 'wordpress-plugin',
                'title' => 'افزونه وردپرس',
                'subtitle' => 'WordPress Plugin',
                'description' => 'لیدهای سایت و فروشگاه مستقیم وارد CRM شود — بدون وارد کردن دستی.',
                'accent' => 'blue',
                'visual' => 'wordpress',
                'audience' => 'صاحبان سایت وردپرسی',
                'features' => ['فرم تماس و ووکامرس وصل می‌شود', 'مشتری و سفارش خودکار ثبت می‌شود', 'دیگر لید گم نمی‌شود'],
                'cta' => 'دمو رایگان افزونه',
                'is_featured' => false,
                'body' => "افزونه وردپرس بیسان پل ارتباطی بین سایت شما و CRM راهبر است.\n\nهر فرم تماس، ثبت‌نام خبرنامه یا سفارش ووکامرس به‌صورت خودکار در CRM ثبت می‌شود. دیگر لازم نیست لیدها را دستی کپی کنید یا در اکسل پیگیری کنید.\n\nنصب ساده، اتصال یک‌باره و همگام‌سازی لحظه‌ای.",
                'highlights' => [
                    ['label' => 'فرم تماس', 'desc' => 'Contact Form 7 و فرم‌های رایج'],
                    ['label' => 'ووکامرس', 'desc' => 'سفارش و مشتری خودکار در CRM'],
                    ['label' => 'همگام لحظه‌ای', 'desc' => 'بدون تأخیر و کار دستی'],
                ],
            ],
        ];
    }

    public function products(): Collection
    {
        return Cache::remember('cms_products', 3600, function () {
            $dbProducts = CmsProduct::query()->published()->orderBy('sort_order')->get();

            if ($dbProducts->isNotEmpty()) {
                return $dbProducts->map(fn(CmsProduct $p) => $this->formatProduct($p));
            }

            return collect();
        });
    }

    public function product(string $slug): ?array
    {
        $product = CmsProduct::query()->published()->where('slug', $slug)->first();

        if ($product) {
            return $this->formatProduct($product);
        }

        return null;
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
            'home' => [
                'title' => 'راهبر حساب | موسسه آموزش حسابداری و خدمات مالی',
                'description' => 'موسسه آموزش حسابداری راهبر حساب — دوره‌های کاربردی حسابداری و مالیات در سراسر ایران.',
                'keywords' => 'راهبر حساب, آموزش حسابداری, مالیات, اظهارنامه, rahbarhesab',
            ],
            'about' => [
                'title' => 'درباره راهبر حساب | موسسه آموزش حسابداری',
                'description' => 'آشنایی با موسسه راهبر حساب، تیم آموزشی و خدمات مالی و مالیاتی.',
                'keywords' => 'درباره راهبر حساب, آموزش حسابداری, موسسه حسابداری',
            ],
            'contact' => [
                'title' => 'تماس با راهبر حساب | مشاوره رایگان',
                'description' => 'تماس با موسسه راهبر حساب برای مشاوره آموزشی و خدمات مالی.',
                'keywords' => 'تماس راهبر حساب, مشاوره حسابداری, پشتیبانی',
            ],
        ];
    }

    public function pageMeta(string $slug): array
    {
        $defaults = $this->defaultPageMeta()[$slug] ?? [];
        $page = CmsPage::query()->published()->where('slug', $slug)->first();

        if (!$page) {
            return $defaults;
        }

        return array_filter([
            'title' => $page->meta_title ?: $page->title,
            'description' => $page->meta_description ?: ($defaults['description'] ?? null),
            'keywords' => $page->meta_keywords ?: ($defaults['keywords'] ?? null),
            'og_title' => $page->meta_title ?: $page->title,
            'og_image' => $page->og_image,
            'robots' => $page->robots ?: ($defaults['robots'] ?? 'index, follow'),
        ]);
    }

    public function defaultPageMeta(): array
    {
        return $this->rahbarPageMeta();
    }

    public function sharedViewData(): array
    {
        return [
            'navLinks' => $this->navLinks(),
            'contact' => $this->contact(),
        ];
    }

    public function clearCache(): void
    {
        Cache::forget('cms_products');
        Cache::forget('cms_home_content');
    }

    public function productImageUrl(?string $image): ?string
    {
        if (!$image) {
            return null;
        }

        if (str_starts_with($image, 'http') || str_starts_with($image, '/')) {
            return $image;
        }

        return Storage::disk('public')->url($image);
    }

    private function salesFields(): array
    {
        return ['stats', 'capabilities', 'use_cases', 'benefits', 'faqs', 'testimonial'];
    }

    private function mergeSalesContent(array $product, array $defaults): array
    {
        foreach ($this->salesFields() as $field) {
            $product[$field] = $defaults[$field] ?? ($product[$field] ?? null);
        }

        return $product;
    }

    private function formatProduct(CmsProduct $product): array
    {
        $defaults = collect($this->defaultProducts())->firstWhere('slug', $product->slug) ?? [];

        return $this->enrichProduct($this->mergeSalesContent([
            'slug' => $product->slug,
            'title' => $product->title,
            'subtitle' => $defaults['subtitle'] ?? $product->subtitle,
            'description' => $defaults['description'] ?? $product->description,
            'accent' => $product->accent,
            'visual' => $product->visual,
            'dashboard_image' => $this->productImageUrl($product->dashboard_image)
                ?? ($defaults['dashboard_image'] ?? null),
            'website_url' => $defaults['website_url'] ?? null,
            'audience' => $defaults['audience'] ?? $product->audience,
            'features' => $defaults['features'] ?? ($product->features ?? []),
            'cta' => $defaults['cta'] ?? $product->cta,
            'body' => $defaults['body'] ?? $product->body,
            'highlights' => $defaults['highlights'] ?? [],
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
            'og_image' => $product->og_image,
            'is_featured' => $product->is_featured,
        ], $defaults));
    }

    private function enrichProduct(array $product): array
    {
        $defaults = collect($this->defaultProducts())->firstWhere('slug', $product['slug'] ?? '') ?? [];

        $product['href'] = route('products.show', $product['slug']);
        $product['dashboard_image'] = $this->productImageUrl($product['dashboard_image'] ?? null)
            ?? ($defaults['dashboard_image'] ?? null);
        $product['website_url'] ??= $defaults['website_url'] ?? null;
        $product['body'] ??= $defaults['body'] ?? null;
        $product['highlights'] ??= $defaults['highlights'] ?? [];

        return $this->mergeSalesContent($product, $defaults);
    }
}
