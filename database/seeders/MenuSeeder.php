<?php

namespace Database\Seeders;

use App\Models\CmsMenu;
use App\Services\CacheService;
use App\Services\MenuService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menu = CmsMenu::query()->updateOrCreate(
            ['slug' => 'primary'],
            ['name' => 'منوی اصلی', 'location' => 'primary']
        );

        app(MenuService::class)->saveTree($menu, $this->tree());

        Cache::forget('cms.menu.primary');
        app(CacheService::class)->flushTag(CacheService::TAG_MENUS);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tree(): array
    {
        return [
            [
                'label' => 'صفحه اصلی',
                'type' => 'route',
                'route_name' => 'home',
            ],
            [
                'label' => 'دوره آموزش حسابداری',
                'type' => 'custom',
                'url' => '#',
                'children' => [
                    $this->courseCategory('بیشترین انتخاب حسابداران🏆', 'accountants-choice'),
                    $this->courseCategory('آموزش های حسابداری بازار کار', 'introductory-accounting-course'),
                    $this->courseCategory('آموزش های مالیاتی بازار کار', 'advanced-accounting-course'),
                    $this->courseCategory('آموزش حسابداری پیمانکاری', 'contractor-accounting-training-courses'),
                    $this->courseCategory('آموزش حسابداری واردات و صادرات', 'import-export-accounting-training-courses'),
                    $this->courseCategory('آموزش حسابداری صنعتی، بهای تمام شده', 'cost-accounting-training-courses'),
                    $this->courseCategory('آموزشهای هدیه (رایگان)', 'free-accounting-course'),
                    [
                        'label' => 'کلیه دوره های آموزش حسابداری',
                        'type' => 'route',
                        'route_name' => 'courses.index',
                    ],
                ],
            ],
            [
                'label' => 'منابع حسابداری',
                'type' => 'custom',
                'url' => '#',
                'children' => [
                    $this->blogCategory('مقالات آموزش حسابداری', 'blogs'),
                    $this->blogCategory('اخبار حسابداری', 'news'),
                    $this->blogCategory('نمونه سوالات', 'sample-questions'),
                    $this->page('فرم قیمت گذاری پروژه توسط راهبرحساب', 'lan-pricingoffancialprojects'),
                    $this->page('محاسبه عیدی و سنوات پایان سال', 'calculation-of-eid-and-year-end-annuities'),
                ],
            ],
            $this->page('خدمات مشاوره مالیاتی', 'tax-consulting-services'),
            [
                'label' => 'محصولات سامانه مودیان',
                'type' => 'custom',
                'url' => '#',
                'children' => [
                    $this->page('نرم افزار سامانه مودیان', 'software'),
                    $this->page('نرم افزار ریموت', 'rahbardesk'),
                    $this->page('دستگاه کارتخوان', 'pos-services'),
                ],
            ],
            [
                'label' => 'ارتباط با ما',
                'type' => 'custom',
                'url' => '#',
                'children' => [
                    [
                        'label' => 'درباره ما',
                        'type' => 'route',
                        'route_name' => 'about',
                    ],
                    [
                        'label' => 'تماس با ما',
                        'type' => 'route',
                        'route_name' => 'contact',
                    ],
                    [
                        'label' => 'نمایندگان',
                        'type' => 'custom',
                        'url' => '#',
                        'children' => [
                            $this->page('نمایندگان آموزش', 'representatives'),
                            $this->page('نمایندگان پایانه های فروشگاهی', 'easy-invoice-representatives'),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function courseCategory(string $label, string $slug): array
    {
        return [
            'label' => $label,
            'type' => 'route',
            'route_name' => 'courses.index',
            'route_params' => ['category' => $slug],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blogCategory(string $label, string $slug): array
    {
        return [
            'label' => $label,
            'type' => 'route',
            'route_name' => 'blog.index',
            'route_params' => ['category' => $slug],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function page(string $label, string $slug): array
    {
        return [
            'label' => $label,
            'type' => 'page',
            'meta' => ['slug' => $slug],
        ];
    }
}
