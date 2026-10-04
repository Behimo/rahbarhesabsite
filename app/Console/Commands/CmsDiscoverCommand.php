<?php

namespace App\Console\Commands;

use App\Services\CategoryService;
use Illuminate\Console\Command;

class CmsDiscoverCommand extends Command
{
    protected $signature = 'cms:discover';

    protected $description = 'Ensure default tags exist';

    public function handle(CategoryService $categories): int
    {
        $categories->ensureDefaultTags();

        $this->info('CMS discovery completed.');

        return self::SUCCESS;
    }
}
