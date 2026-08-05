<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsSetting;
use Illuminate\Support\Facades\Cache;

class HomeContentService
{
    public function __construct(private SiteDataService $siteData) {}

    public function all(): array
    {
        return Cache::remember('cms_home_content', 3600, function () {
            $page = CmsPage::query()->where('slug', 'home')->first();
            $stored = $page?->content ?? [];

            return [
                'hero' => array_merge($this->defaultHero(), $stored['hero'] ?? []),
                'stats' => $stored['stats'] ?? $this->siteData->stats(),
                'why_bisan' => $stored['why_bisan'] ?? $this->siteData->whyBisan(),
                'partners' => $stored['partners'] ?? $this->siteData->partners(),
                'testimonials' => $stored['testimonials'] ?? $this->defaultTestimonials(),
                'trust_badges' => $stored['trust_badges'] ?? $this->defaultTrustBadges(),
                'cta' => array_merge($this->defaultCta(), $stored['cta'] ?? []),
            ];
        });
    }

    public function save(array $content): void
    {
        $page = CmsPage::query()->firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'صفحه اصلی',
                'meta_title' => 'بیسان | BISAN Holding',
                'is_published' => true,
                'is_system' => true,
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

    public function latestPosts(int $limit = 3)
    {
        return CmsPost::query()
            ->published()
            ->with('category')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    public function defaultHero(): array
    {
        return [
            'eyebrow' => 'تیم برنامه‌نویسی · ۴ محصول زنده · +۱۰ سال تجربه',
            'title_line1' => 'ما نرم‌افزار می‌سازیم که',
            'rotate_words' => ['فروش را رشد دهد', 'کار را ساده کند', 'کسب‌وکار را متحول کند'],
            'title_line2' => '— حرفه‌ای و در مقیاس',
            'lead' => 'تیم مهندسی بیسان هر روز روی ۴ محصول فعال کار می‌کند — و اگر نیاز شما فرق دارد، همان را از صفر برایتان می‌سازیم. کد تمیز، معماری درست، تحویل به‌موقع.',
            'cta_primary' => 'مشاوره رایگان',
            'cta_secondary' => 'ببینید چه ساخته‌ایم',
            'chips' => ['Laravel & PHP', 'API یکپارچه', 'پشتیبانی فارسی', 'تحویل سریع'],
            'pills' => $this->siteData->heroPills(),
        ];
    }

    public function defaultTestimonials(): array
    {
        return [
            [
                'name' => 'سارا محمدی',
                'role' => 'مدیر فروش، شرکت توزیع کالا',
                'text' => 'بعد از راهبر، تیم فروش ما ۴۰٪ سریع‌تر به قرارداد می‌رسد. قیف فروش و یادآور خودکار واقعاً تفاوت ایجاد کرد.',
                'rating' => 5,
            ],
            [
                'name' => 'امیر کریمی',
                'role' => 'مدیر محصول، استارتاپ SaaS',
                'text' => 'نوژارو زیرساخت چندمجموعه‌ای ما را در ۲ هفته آماده کرد. مدیریت اشتراک، پرداخت و ماژول‌ها از یک پنل — دقیقاً همان چیزی که نیاز داشتیم.',
                'rating' => 5,
            ],
            [
                'name' => 'امیر حسینی',
                'role' => 'صاحب فروشگاه آنلاین',
                'text' => 'افزونه وردپرس لیدهای سایت را مستقیم به CRM می‌فرستد. دیگر لازم نیست دستی کپی کنیم.',
                'rating' => 5,
            ],
        ];
    }

    public function defaultTrustBadges(): array
    {
        return [
            ['icon' => 'shield', 'label' => '۱۰ سال تجربه'],
            ['icon' => 'users', 'label' => '+۱۲۰۰ مشتری'],
            ['icon' => 'support', 'label' => 'پشتیبانی ۲۴/۷'],
            ['icon' => 'star', 'label' => '۹۸٪ رضایت'],
        ];
    }

    public function defaultCta(): array
    {
        return [
            'title' => 'آماده شروع هستید؟',
            'subtitle' => 'محصول آماده یا پروژه اختصاصی — با یک تماس راه را پیدا می‌کنیم.',
            'primary' => 'مشاوره رایگان',
            'secondary' => 'پروژه اختصاصی',
        ];
    }
}
