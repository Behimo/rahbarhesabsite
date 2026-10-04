<?php

namespace Database\Seeders;

use App\Support\AccessCatalog;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        AccessCatalog::install();
    }
}
