<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cms_posts', 'shop_products'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('robots', 500)->nullable()->change());
        }
        Schema::table('cms_pages', fn (Blueprint $table) => $table->string('robots', 500)->default('index, follow')->change());
    }

    public function down(): void
    {
        foreach (['cms_posts', 'shop_products'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('robots', 64)->nullable()->change());
        }
        Schema::table('cms_pages', fn (Blueprint $table) => $table->string('robots')->default('index, follow')->change());
    }
};
