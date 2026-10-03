<?php

namespace App\Console\Commands;

use App\Services\CategoryService;
use App\Services\PluginService;
use Illuminate\Console\Command;

class CmsDiscoverCommand extends Command
{
    protected $signature = 'cms:discover';

    protected $description = 'Discover plugins and default tags';

    public function handle(PluginService $plugins, CategoryService $categories): int
    {
        $plugins->discover();
        $categories->ensureDefaultTags();

        $this->info('CMS discovery completed.');

        return self::SUCCESS;
    }
}
