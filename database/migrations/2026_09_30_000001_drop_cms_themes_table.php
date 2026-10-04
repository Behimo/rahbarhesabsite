<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cms_themes');
    }

    public function down(): void
    {
        // Theme selection was removed from the site.
    }
};
