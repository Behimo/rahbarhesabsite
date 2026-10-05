<?php

use App\Support\AccessCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AccessCatalog::install();
        AccessCatalog::expandLegacyPermissions();
    }

    public function down(): void
    {
        AccessCatalog::install();
    }
};
