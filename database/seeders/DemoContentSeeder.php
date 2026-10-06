<?php

namespace Database\Seeders;

use Database\Seeders\Demo\DemoBlogSeeder;
use Database\Seeders\Demo\DemoCourseSeeder;
use Illuminate\Database\Seeder;

final class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoContentSeeder در production اجرا نشد.');

            return;
        }

        $this->call([
            DemoBlogSeeder::class,
            DemoCourseSeeder::class,
            ProductCategorySeeder::class,
        ]);
    }
}
