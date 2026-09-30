<?php

namespace App\Console\Commands;

use App\Services\PluginService;
use App\Services\TaxonomyService;
use Illuminate\Console\Command;

class CmsDiscoverCommand extends Command
{
    protected $signature = 'cms:discover';

    protected $description = 'Discover plugins and default taxonomies';

    public function handle(PluginService $plugins, TaxonomyService $taxonomy): int
    {
        $plugins->discover();
        $taxonomy->ensureDefaults();

        $this->info('CMS discovery completed.');

        return self::SUCCESS;
    }
}
