<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Services\CacheService;
use App\Services\HomePageDefaults;
use Illuminate\Database\Seeder;

class HomeBlocksSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = app(HomePageDefaults::class);

        $page = CmsPage::query()->firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'صفحه اصلی',
                'is_published' => true,
                'is_system' => true,
                'template' => 'system',
                'status' => 'published',
                'sort_order' => 1,
            ]
        );

        $content = is_array($page->builder_content) ? $page->builder_content : [];
        $content = empty($content['blocks'] ?? null)
            ? $defaults->builderContent()
            : $defaults->hydrate($content);

        $page->update([
            'builder_enabled' => true,
            'builder_content' => $content,
        ]);

        app(CacheService::class)->flushContent();
    }
}
