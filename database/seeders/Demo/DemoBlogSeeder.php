<?php

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Builders\DemoBlogBuilder;
use Database\Seeders\Demo\Catalogs\AccountingBlogCatalog;
use Illuminate\Database\Seeder;

final class DemoBlogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = app(AccountingBlogCatalog::class);
        $builder = app(DemoBlogBuilder::class);

        $categories = $builder->syncCategories($catalog->categories());
        $builder->syncPosts($categories, $catalog->posts());

        $this->command?->info(sprintf(
            'Demo blog ready: %d categories, %d posts.',
            count($catalog->categories()),
            count($catalog->posts())
        ));
    }
}
