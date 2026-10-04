<?php

namespace App\Services;

class HomePageDefaults
{
    /** @return array<string, mixed> */
    public function builderContent(): array
    {
        return [
            'blocks' => [
                ['type' => 'hero', 'settings' => $this->heroSettings()],
                ['type' => 'feature-split', 'settings' => $this->section('app')],
                ['type' => 'features', 'settings' => ['items' => $this->featureCards()]],
                ['type' => 'courses', 'settings' => [
                    'title' => 'جدیدترین دوره های ما',
                    'section_id' => 'latestCourses',
                    'section_class' => '',
                    'limit' => 12,
                    'filter' => 'latest',
                ]],
                ['type' => 'feature-split', 'settings' => $this->section('finance')],
                ['type' => 'courses', 'settings' => [
                    'title' => 'دوره های رایگان',
                    'section_id' => 'freeCourses',
                    'section_class' => 'free-courses-section',
                    'limit' => 6,
                    'filter' => 'free',
                ]],
                ['type' => 'faq', 'settings' => [
                    'title' => 'پرتکرارترین سوالات حسابداری',
                    'items' => $this->faqItems(),
                ]],
                ['type' => 'posts', 'settings' => [
                    'title' => 'اخبار و بخشنامه های جدید',
                    'limit' => 8,
                    'items' => $this->newsItems(),
                ]],
                ['type' => 'feature-split', 'settings' => $this->section('system')],
                ['type' => 'feature-split', 'settings' => $this->section('instagram')],
                ['type' => 'feature-split', 'settings' => $this->section('mentor')],
                ['type' => 'feature-split', 'settings' => $this->section('experiences')],
                ['type' => 'feature-split', 'settings' => $this->section('partners')],
            ],
        ];
    }

    /**
     * Fill blank block settings from the static defaults.
     * Text the editor already saved stays in place.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public function hydrate(array $content): array
    {
        $defaults = $this->builderContent()['blocks'];
        $used = [];
        $blocks = [];

        foreach ($content['blocks'] ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $default = $this->matchDefault($defaults, $block, $used);
            if ($default !== null) {
                $block['settings'] = $this->mergeSettings(
                    $block['type'] ?? '',
                    is_array($block['settings'] ?? null) ? $block['settings'] : [],
                    $default['settings']
                );
            }

            $blocks[] = $block;
        }

        return ['blocks' => $blocks];
    }

    /** @return array<string, mixed> */
    public function heroSettings(): array
    {
        return [
            'eyebrow' => 'راهبر حساب؛ خالق رهبران حسابداری',
            'title' => 'موسسه آموزشی حسابداری و خدمات مالی و مالیاتی',
            'subtitle' => 'آموزش تخصصی حسابداری برای بازار کار و ارائه خدمات مالی و مالیاتی به حسابداران و کسب و کارها در سراسر ایران؛ برگزارکننده نخستین همایش ۱۰۰۰ نفره حسابداری در کشور',
            'cta_text' => '',
            'cta_url' => '#',
            'background_image' => 'site/images/rahbar-hesab-new-banner.webp',
        ];
    }

    /** @return array<string, mixed> */
    public function section(string $layout): array
    {
        return match ($layout) {
            'finance' => $this->split(
                'finance',
                'راهبر مالی، خدمات تخصصی مالی و مالیاتی برای شرکت ها',
                'اگر می خواهید امور مالی و حسابداری شرکتتان کاملاً اصولی و مطابق با قوانین پیش برود و نگرانی بابت پرداخت مالیات غیرعادلانه نداشته باشید مجموعه ما اینجاست تا همراهتان باشد!',
                'سایر خدمات راهبر مالی',
                '/about',
                'site/images/99605e6a-3a2b-44b4-a380-a9980fb0b822.png',
                [
                    ['title' => '', 'text' => 'ارائه خدمات مالی و مالیاتی تخصصی'],
                    ['title' => '', 'text' => 'انجام تمامی تکالیف قانونی'],
                    ['title' => '', 'text' => 'پشتیبانی حرفه ای و همراهی مستمر'],
                    ['title' => '', 'text' => 'پرداخت مالیات عادلانه و بدون دغدغه'],
                ]
            ),
            'system' => $this->split(
                'system',
                'ارائه نرم افزار راهبر سیستم ( ایزی اینویس )',
                'واسط سامانه مودیان – ارسال نامحدود، بدون دردسر! به کمک نرم افزار تحت وب «راهبر سیستم»، می توانید به سادگی و با کمترین هزینه فاکتورهای مالیاتی خود را به سامانه مودیان ارسال کنید و نگرانی بابت پرداخت مالیات غیرعادلانه نداشته باشید مجموعه ما اینجاست تا همراهتان باشد!',
                'نرم افزار راهبر سیستم',
                '/courses',
                'site/images/vida-mohammadnia.webp',
                [
                    ['title' => '', 'text' => 'ارسال نامحدود فاکتور'],
                    ['title' => '', 'text' => 'پشتیبانی رایگان ۹ صبح تا ۹ شب'],
                    ['title' => '', 'text' => 'ورود گروهی فاکتورها با اکسل'],
                    ['title' => '', 'text' => 'دریافت کلید خصوصی و عمومی رایگان'],
                    ['title' => '', 'text' => 'آموزش رایگان کار با نرم افزار'],
                ]
            ),
            'instagram' => $this->split(
                'instagram',
                'ما رو در اینستاگرام دنبال کن',
                'ما روزانه مطالب آموزشی و اخبار فوری حسابداری رو در اینستاگرام به اشتراک میگذاریم',
                'اینستاگرام راهبر حساب',
                'https://instagram.com/rahbarhesab',
                'site/images/instagramupdate.webp',
                [
                    ['title' => 'نکات آموزشی', 'text' => 'نکات آموزشی حسابداری و مالیات'],
                    ['title' => 'آخرین تخفیفات', 'text' => 'تخفیف‌های ویژه دوره‌ها و خدمات'],
                    ['title' => 'بخشنامه ها', 'text' => 'جدیدترین بخشنامه‌های مالیاتی'],
                ]
            ),
            'mentor' => $this->split(
                'mentor',
                'راهبر مشاور پل ارتباطی مطمئن با بهترین مشاوران مالی و مالیاتی',
                'راهبر مشاور با نام سابق منتورما راهکاری ساده و سریع برای دریافت انواع مشاوره تخصصی است؛ برای هر نوع مشاوره، به ویژه در زمینه مالی و مالیاتی!',
                'خدمات راهبر مشاور',
                '/about',
                'site/images/group-all-mentorma.webp',
                [
                    ['title' => '', 'text' => 'دسترسی به بهترین مشاوران مالی و مالیاتی'],
                    ['title' => '', 'text' => 'انواع روش های مشاوره'],
                    ['title' => '', 'text' => 'امتیاز و نظرسنجی های واقعی'],
                    ['title' => '', 'text' => 'مشاوره تلفنی هوشمند'],
                    ['title' => '', 'text' => 'تنوع مشاوران و خدمات'],
                    ['title' => '', 'text' => 'کیفیت بالای مشاوره های حضوری'],
                ]
            ),
            'experiences' => $this->split(
                'experiences',
                'تجربیات دانشجویان ما',
                '',
                'تجربیات بیشتر',
                '/blog',
                '',
                [
                    ['title' => 'تجربه دانشجوی دوره VIP راهبر حساب', 'text' => '', 'image' => 'site/images/experience-1.jpg', 'url' => '#'],
                    ['title' => 'تجربه دانشجوی راهبر حساب', 'text' => '', 'image' => 'site/images/experience-2.jpg', 'url' => '#'],
                    ['title' => 'تجربه دانشجوی دوره اظهارنامه VIP', 'text' => '', 'image' => 'site/images/experience-3.jpg', 'url' => '#'],
                ]
            ),
            'partners' => $this->split(
                'partners',
                'همکاران راهبر حساب',
                '',
                '',
                '',
                '',
                [
                    ['title' => 'سپیدار سیستم', 'text' => '', 'image' => 'site/images/partner-sepidar.png', 'url' => '#'],
                    ['title' => 'هلو', 'text' => '', 'image' => 'site/images/partner-holoo.png', 'url' => '#'],
                    ['title' => 'راهبر آکادمی', 'text' => '', 'image' => 'site/images/partner-rahbar-academy.png', 'url' => '#'],
                ]
            ),
            default => $this->split(
                'app',
                'اپلیکیشن راهبر رو نصب کن...',
                'همه حسابدارا اینجان! تنها اپلیکیشن حسابداری ایران...',
                'دانلود رایگان اپلیکیشن حسابداران',
                '#',
                'site/images/apkrahbar2163.webp',
                [
                    ['title' => 'بخشنامه ها و اخبار', 'text' => 'بخشنامه و اخبار جدید'],
                    ['title' => 'تقویم وظایف حسابدار', 'text' => 'یادآور مهلت های قانونی'],
                    ['title' => 'پشتیبانی تخصصی', 'text' => 'دریافت رایگان پاسخ سوالات'],
                ]
            ),
        };
    }

    /** @return array<int, array<string, string>> */
    public function featureCards(): array
    {
        return [
            [
                'title' => '+20 دوره کاربردی',
                'text' => 'آموزش ها کاملا عملی، ویژه بازارکار و کاربردی با بالاترین کیفیت هستند و پشتیبانی دوره ها توسط کادر مجرب موسسه انجام می‌شود',
                'variant' => 'down',
                'panel' => 'gray',
            ],
            [
                'title' => 'ارائه مدارک معتبر',
                'text' => 'مدرک ویژه مجموعه جهت ورود به بازار کار، مدرک فنی حرفه ای و مدرک CIP ویژه حسابداران برگزیده',
                'variant' => 'up',
                'panel' => 'white',
            ],
            [
                'title' => 'کافه سوال حسابداری',
                'text' => 'مرجع تخصصی برای دریافت پاسخ های دقیق به سوالات مالی و مالیاتی',
                'variant' => 'down',
                'panel' => 'gray',
            ],
            [
                'title' => '+100 مشاور حرفه ای',
                'text' => 'ارائه خدمات مالی و مالیاتی از طریق کارشناسان معتبر و خبره',
                'variant' => 'up',
                'panel' => 'white',
            ],
            [
                'title' => 'پشتیبانی 7/24',
                'text' => 'پشتیبانی دوره ها به صورت پیام متنی، تماس تلفنی و حتی ریموت روی سیستم توسط کادر مجرب موسسه انجام می‌شود',
                'variant' => 'down',
                'panel' => 'gray',
            ],
        ];
    }

    /** @return array<int, array<string, string>> */
    public function faqItems(): array
    {
        return [
            ['question' => 'شناسه عمومی مشابه کالا و خدمات در سامانه مودیان', 'answer' => 'شناسه عمومی کالا و خدمات از سامانه مودیان برای یکسان‌سازی اقلام فاکتور استفاده می‌شود.', 'href' => '/blog'],
            ['question' => 'پرداخت حقوق قبل از تهیه کد کارگاهی و ثبت بیمه از کارگاه غیره', 'answer' => 'ثبت حقوق و بیمه باید مطابق مقررات تأمین اجتماعی و با کد کارگاهی معتبر انجام شود.', 'href' => '/blog'],
            ['question' => 'مهلت واکنش به جزئیات اطلاعات معاملات سامانه ارزش افزوده', 'answer' => 'مهلت واکنش به جزئیات معاملات در سامانه ارزش افزوده طبق اطلاعیه سازمان امور مالیاتی است.', 'href' => '/blog'],
            ['question' => 'اعتراض به برگ تشخیص سیستمی', 'answer' => 'اعتراض به برگ تشخیص سیستمی از طریق سامانه مربوط و در مهلت قانونی امکان‌پذیر است.', 'href' => '/blog'],
            ['question' => 'نحوه اصلاح صورتحساب الکترونیکی در سامانه مؤدیان', 'answer' => 'اصلاح صورتحساب الکترونیکی با صدور صورتحساب اصلاحی یا ابطالی در سامانه مؤدیان انجام می‌شود.', 'href' => '/blog'],
            ['question' => 'نحوه ثبت هزینه های قابل قبول مالیاتی', 'answer' => 'هزینه‌های قابل قبول باید مستند، مرتبط با فعالیت و مطابق قانون مالیات‌های مستقیم باشند.', 'href' => '/blog'],
        ];
    }

    /** @return array<int, array<string, string>> */
    public function defaultFaqs(): array
    {
        return array_map(fn (array $item) => [
            'q' => $item['question'],
            'a' => $item['answer'],
            'href' => $item['href'] ?: '/blog',
        ], $this->faqItems());
    }

    /** @return array<int, array<string, string>> */
    public function newsItems(): array
    {
        return [
            ['title' => 'کارپوشه تجاری و غیر تجاری در سامانه مودیان', 'subtitle' => 'شناسایی حساب‌های تجاری و ایجاد پرونده', 'href' => '/blog'],
            ['title' => 'آخرین مهلت ارسال صورت معاملات فصلی', 'subtitle' => 'پاییز ۱۴۰۴', 'href' => '/blog'],
            ['title' => 'آخرین مهلت ارسال اظهارنامه مالیات بر ارزش افزوده', 'subtitle' => 'بهار ۱۴۰۵', 'href' => '/blog'],
            ['title' => 'بخشودگی جرائم و تقسیط بدهی', 'subtitle' => 'مودیان فراخوان شده ارسال صورتحساب الکترونیک', 'href' => '/blog'],
            ['title' => 'الزام پرداخت مالیات ۸ درصدی با صدور صورتحساب الکترونیکی', 'subtitle' => 'ویژه برخی اصناف', 'href' => '/blog'],
            ['title' => 'مالیات تراکنش های بانکی', 'subtitle' => 'سال ۱۴۰۴', 'href' => '/blog'],
            ['title' => 'تیپ شخصیتی مناسب شغل حسابداری بر اساس MBTI', 'subtitle' => 'عمومی', 'href' => '/blog'],
            ['title' => 'تمدید ارسال دفاتر الکترونیکی', 'subtitle' => 'نیمه دوم سال ۱۴۰۴', 'href' => '/blog'],
        ];
    }

    /** @return array<int, array<string, string>> */
    public function defaultFallbackNews(): array
    {
        return $this->newsItems();
    }

    /**
     * @param  array<int, array<string, mixed>>  $defaults
     * @param  array<string, mixed>  $block
     * @param  array<int, bool>  $used
     * @return array<string, mixed>|null
     */
    private function matchDefault(array $defaults, array $block, array &$used): ?array
    {
        foreach ($defaults as $index => $default) {
            if (isset($used[$index]) || ($default['type'] ?? '') !== ($block['type'] ?? '')) {
                continue;
            }

            $defaultSettings = $default['settings'] ?? [];
            $settings = $block['settings'] ?? [];

            if (($block['type'] ?? '') === 'feature-split' && ($defaultSettings['layout'] ?? '') !== ($settings['layout'] ?? '')) {
                continue;
            }

            if (($block['type'] ?? '') === 'courses' && ($defaultSettings['section_id'] ?? '') !== ($settings['section_id'] ?? '')) {
                continue;
            }

            $used[$index] = true;

            return $default;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function mergeSettings(string $type, array $current, array $defaults): array
    {
        if ($type === 'feature-split' && $this->repeaterEmpty($current['items'] ?? null)) {
            return $defaults;
        }

        if ($type === 'features' && $this->featuresNeedDefaults($current['items'] ?? null)) {
            $current['items'] = $defaults['items'] ?? [];
        }

        foreach ($defaults as $key => $value) {
            if (is_array($value) && array_is_list($value)) {
                if ($this->repeaterEmpty($current[$key] ?? null)) {
                    $current[$key] = $value;
                }

                continue;
            }

            if (! array_key_exists($key, $current) || $current[$key] === '' || $current[$key] === null) {
                $current[$key] = $value;
            }
        }

        return $current;
    }

    private function repeaterEmpty(mixed $items): bool
    {
        if (! is_array($items) || $items === []) {
            return true;
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach ($item as $value) {
                if (is_string($value) && trim($value) !== '') {
                    return false;
                }
            }
        }

        return true;
    }

    private function featuresNeedDefaults(mixed $items): bool
    {
        if ($this->repeaterEmpty($items)) {
            return true;
        }

        foreach ($items as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            if ($title !== '' && ! str_starts_with($title, 'تست')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, array<string, string>>  $items
     * @return array<string, mixed>
     */
    private function split(string $layout, string $heading, string $description, string $ctaText, string $ctaUrl, string $image, array $items): array
    {
        return [
            'layout' => $layout,
            'heading' => $heading,
            'description' => $description,
            'image' => $image,
            'cta_text' => $ctaText,
            'cta_url' => $ctaUrl,
            'items' => array_map(fn (array $item) => [
                'title' => $item['title'] ?? '',
                'text' => $item['text'] ?? '',
                'image' => $item['image'] ?? '',
                'url' => $item['url'] ?? '',
            ], $items),
        ];
    }
}
