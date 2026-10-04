<?php

namespace Database\Seeders;

use App\Services\CategoryService;
use Illuminate\Database\Seeder;

class CmsExtensionsSeeder extends Seeder
{
    public function run(): void
    {
        app(CategoryService::class)->ensureDefaultTags();
    }
}
