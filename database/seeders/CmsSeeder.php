<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Services\HomePageDefaults;
use App\Services\SiteDataService;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $siteData = app(SiteDataService::class);
        $homeDefaults = app(HomePageDefaults::class);

        foreach ($siteData->rahbarPageMeta() as $slug => $meta) {
            $payload = [
                'title' => $meta['title'],
                'meta_title' => $meta['title'],
                'meta_description' => $meta['description'],
                'meta_keywords' => $meta['keywords'] ?? null,
                'robots' => 'index, follow',
                'is_published' => true,
                'is_system' => true,
                'template' => 'system',
                'status' => 'published',
                'sort_order' => match ($slug) {
                    'home' => 1,
                    'about' => 2,
                    'contact' => 3,
                    default => 99,
                },
            ];

            if ($slug === 'home') {
                $payload['builder_enabled'] = true;
                $existing = CmsPage::query()->where('slug', $slug)->first();

                if ($existing === null || empty($existing->builder_content['blocks'] ?? null)) {
                    $payload['builder_content'] = $homeDefaults->builderContent();
                }
            }

            CmsPage::query()->updateOrCreate(['slug' => $slug], $payload);
        }

        $category = Category::query()->updateOrCreate(
            ['type' => Category::TYPE_POST, 'slug' => 'accounting-guides', 'parent_id' => null],
            ['name' => 'راهنمای حسابداری', 'description' => 'مقالات آموزشی حسابداری و مالیات', 'sort_order' => 1, 'is_active' => true]
        );

        $samplePosts = [
            [
                'slug' => 'tax-declaration-guide',
                'title' => 'راهنمای ثبت اظهارنامه مالیاتی',
                'excerpt' => 'مراحل ثبت اظهارنامه و نکات مهم برای مشاغل.',
                'body' => '<p>در این مقاله مراحل ثبت اظهارنامه مالیاتی را مرور می‌کنیم.</p>',
            ],
            [
                'slug' => 'vat-deadline',
                'title' => 'مهلت ارسال اظهارنامه ارزش افزوده',
                'excerpt' => 'زمان‌بندی فصلی ارسال اظهارنامه ارزش افزوده.',
                'body' => '<p>مهلت ارسال معمولاً یک ماه پس از پایان هر فصل است.</p>',
            ],
        ];

        foreach ($samplePosts as $i => $post) {
            $saved = CmsPost::query()->updateOrCreate(
                ['slug' => $post['slug']],
                [
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'body' => $post['body'],
                    'author' => 'راهبر حساب',
                    'is_published' => true,
                    'status' => 'published',
                    'published_at' => now()->subDays($i + 1),
                    'meta_title' => $post['title'],
                ]
            );
            $saved->syncCategories([$category->id], $category->id);
        }
    }
}
