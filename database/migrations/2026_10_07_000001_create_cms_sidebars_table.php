<?php

use App\Support\AccessCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_sidebars')) {
            Schema::create('cms_sidebars', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('title');
                $table->boolean('is_active')->default(true);
                $table->string('target_mode')->default('pages');
                $table->json('pages')->nullable();
                $table->json('rules')->nullable();
                $table->string('source');
                $table->string('selection');
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->unsignedTinyInteger('limit')->default(5);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        AccessCatalog::install();
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_sidebars');
        AccessCatalog::install();
    }
};
