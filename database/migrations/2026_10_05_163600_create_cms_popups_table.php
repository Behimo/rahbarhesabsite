<?php

use App\Support\AccessCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_popups')) {
            Schema::create('cms_popups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('title');
                $table->text('body');
                $table->string('image_url')->nullable();
                $table->string('button_label')->nullable();
                $table->string('button_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('target_mode')->default('pages');
                $table->json('pages')->nullable();
                $table->json('rules')->nullable();
                $table->unsignedSmallInteger('delay_seconds')->default(0);
                $table->string('frequency')->default('session');
                $table->string('audience')->default('all');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        AccessCatalog::install();
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_popups');
        AccessCatalog::install();
    }
};
