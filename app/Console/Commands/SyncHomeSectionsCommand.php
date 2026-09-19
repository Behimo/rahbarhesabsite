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

        if (! $this->option('force') && ! empty($page->builder_content['blocks'])) {
            $this->warn('Home page already has builder content. Use --force to overwrite.');

            return self::SUCCESS;
        }

        $page->update([
            'builder_enabled' => true,
            'builder_content' => $defaults->builderContent(),
        ]);

        $this->info('Home page sections synced successfully.');

        return self::SUCCESS;
    }
}
