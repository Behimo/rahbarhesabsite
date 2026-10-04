<?php

namespace App\Console\Commands;

use App\Models\CmsPage;
use App\Services\HomePageDefaults;
use Illuminate\Console\Command;

class SyncHomeSectionsCommand extends Command
{
    protected $signature = 'cms:sync-home-sections {--force : Overwrite existing builder content}';

    protected $description = 'Seed the home page with default rahbarhesab theme sections';

    public function handle(HomePageDefaults $defaults): int
    {
        $page = CmsPage::query()->firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'صفحه اصلی',
                'is_published' => true,
                'is_system' => true,
                'status' => 'published',
                'sort_order' => 1,
            ]
        );

        $content = $this->option('force') || empty($page->builder_content['blocks'])
            ? $defaults->builderContent()
            : $defaults->hydrate($page->builder_content);

        $page->update([
            'builder_enabled' => true,
            'builder_content' => $content,
        ]);

        $this->info('Home page sections synced successfully.');

        return self::SUCCESS;
    }
}
