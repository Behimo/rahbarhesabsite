<?php

namespace App\Console\Commands;

use App\Services\PluginService;
use App\Services\TaxonomyService;
use App\Services\ThemeService;
use Illuminate\Console\Command;

class CmsDiscoverCommand extends Command
{
    protected $signature = 'cms:discover';

    protected $description = 'Discover themes, plugins and default taxonomies';

    public function handle(ThemeService $themes, PluginService $plugins, TaxonomyService $taxonomy): int
    {
        $themes->discover();
        $plugins->discover();
        $taxonomy->ensureDefaults();

        $this->info('CMS discovery completed.');

        return self::SUCCESS;
    }
}
