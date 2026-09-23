<?php

namespace Database\Seeders;

use Database\Seeders\Demo\DemoBlogSeeder;
use Database\Seeders\Demo\DemoCourseSeeder;
use Illuminate\Database\Seeder;

final class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoBlogSeeder::class,
            DemoCourseSeeder::class,
        ]);
    }
}
