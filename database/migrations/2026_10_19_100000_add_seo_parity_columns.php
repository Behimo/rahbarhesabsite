<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_posts', 'robots')) {
                $table->string('robots', 64)->nullable()->after('og_image');
            }
            if (! Schema::hasColumn('cms_posts', 'canonical_url')) {
                $table->string('canonical_url', 500)->nullable()->after('robots');
            }
            if (! Schema::hasColumn('cms_posts', 'wp_post_id')) {
                $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
            }
        });

        Schema::table('cms_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_pages', 'canonical_url')) {
                $table->string('canonical_url', 500)->nullable()->after('robots');
            }
            if (! Schema::hasColumn('cms_pages', 'wp_post_id')) {
                $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
            }
        });

        Schema::table('shop_products', function (Blueprint $table) {
            if (! Schema::hasColumn('shop_products', 'meta_keywords')) {
                $table->string('meta_keywords')->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('shop_products', 'og_image')) {
                $table->string('og_image')->nullable()->after('meta_keywords');
            }
            if (! Schema::hasColumn('shop_products', 'robots')) {
                $table->string('robots', 64)->nullable()->after('og_image');
            }
            if (! Schema::hasColumn('shop_products', 'canonical_url')) {
                $table->string('canonical_url', 500)->nullable()->after('robots');
            }
            if (! Schema::hasColumn('shop_products', 'wp_post_id')) {
                $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
            }
        });

        if (Schema::hasTable('cms_redirects') && ! Schema::hasColumn('cms_redirects', 'status_code')) {
            // already exists in original migration
        }

        Schema::table('cms_redirects', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_redirects', 'match_query')) {
                $table->boolean('match_query')->default(false)->after('from_path');
            }
            if (! Schema::hasColumn('cms_redirects', 'from_query')) {
                $table->string('from_query', 255)->nullable()->after('match_query');
            }
            if (! Schema::hasColumn('cms_redirects', 'source')) {
                $table->string('source', 32)->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_posts', function (Blueprint $table) {
            foreach (['robots', 'canonical_url', 'wp_post_id'] as $column) {
                if (Schema::hasColumn('cms_posts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('cms_pages', function (Blueprint $table) {
            foreach (['canonical_url', 'wp_post_id'] as $column) {
                if (Schema::hasColumn('cms_pages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('shop_products', function (Blueprint $table) {
            foreach (['meta_keywords', 'og_image', 'robots', 'canonical_url', 'wp_post_id'] as $column) {
                if (Schema::hasColumn('shop_products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('cms_redirects', function (Blueprint $table) {
            foreach (['match_query', 'from_query', 'source'] as $column) {
                if (Schema::hasColumn('cms_redirects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
