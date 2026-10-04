<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cms_blocks');
    }

    public function down(): void
    {
        // Unused block library. Page blocks live in cms_pages.builder_content.
    }
};
