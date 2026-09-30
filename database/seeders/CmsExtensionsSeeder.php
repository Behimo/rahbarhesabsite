<?php

namespace Database\Seeders;

use App\Services\TaxonomyService;
use Illuminate\Database\Seeder;

class CmsExtensionsSeeder extends Seeder
{
    public function run(): void
    {
        app(TaxonomyService::class)->ensureDefaults();
    }
}
