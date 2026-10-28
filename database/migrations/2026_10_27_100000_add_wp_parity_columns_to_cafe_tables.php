<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafe_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
            $table->string('meta_title', 191)->nullable();
            foreach (['meta_description', 'robots', 'canonical_url', 'og_image', 'featured_image', 'video_url', 'video_poster'] as $column) {
                $table->string($column, 500)->nullable();
            }
            $table->longText('body')->change();
        });
        Schema::table('cafe_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_comment_id')->nullable()->unique();
            $table->foreignId('parent_id')->nullable()->constrained('cafe_answers')->nullOnDelete();
            $table->string('author_name', 191)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->longText('body')->change();
        });
    }

    public function down(): void
    {
        Schema::table('cafe_answers', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropUnique(['wp_comment_id']);
            $table->dropColumn(['wp_comment_id', 'parent_id', 'author_name']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->text('body')->change();
        });
        Schema::table('cafe_questions', function (Blueprint $table) {
            $table->dropUnique(['wp_post_id']);
            $table->dropColumn(['wp_post_id', 'meta_title', 'meta_description', 'robots', 'canonical_url', 'og_image', 'featured_image', 'video_url', 'video_poster']);
            $table->text('body')->change();
        });
    }
};
