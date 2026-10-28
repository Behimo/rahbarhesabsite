<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_posts', function (Blueprint $table) {
            $table->string('robots', 64)->nullable()->after('og_image');
            $table->string('canonical_url', 500)->nullable()->after('robots');
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
        });
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->string('canonical_url', 500)->nullable()->after('robots');
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
        });
        Schema::table('shop_products', function (Blueprint $table) {
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->string('og_image')->nullable()->after('meta_keywords');
            $table->string('robots', 64)->nullable()->after('og_image');
            $table->string('canonical_url', 500)->nullable()->after('robots');
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
        });
        Schema::table('cms_redirects', function (Blueprint $table) {
            $table->boolean('match_query')->default(false)->after('from_path');
            $table->string('from_query', 255)->nullable()->after('match_query');
            $table->string('source', 32)->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        // Reverse the later composite redirect index migration before this one.
        foreach (['cms_posts', 'cms_pages', 'shop_products'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropUnique(['wp_post_id']);
            });
        }
        Schema::table('cms_posts', fn (Blueprint $table) => $table->dropColumn(['robots', 'canonical_url', 'wp_post_id']));
        // cms_pages.robots belongs to the original CMS migration, not this one.
        Schema::table('cms_pages', fn (Blueprint $table) => $table->dropColumn(['canonical_url', 'wp_post_id']));
        Schema::table('shop_products', fn (Blueprint $table) => $table->dropColumn(['meta_keywords', 'og_image', 'robots', 'canonical_url', 'wp_post_id']));
        Schema::table('cms_redirects', fn (Blueprint $table) => $table->dropColumn(['match_query', 'from_query', 'source']));
    }
};
