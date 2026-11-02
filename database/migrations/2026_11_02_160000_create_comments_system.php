<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->string('author_ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->morphs('commentable');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->text('body');
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_admin_reply')->default(false);
            $table->unsignedTinyInteger('spam_score')->nullable();
            $table->json('spam_reasons')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('edited_at')->nullable();
            $table->unsignedBigInteger('wp_comment_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('cms_posts', function (Blueprint $table) {
            $table->string('comments_status', 20)->default('inherit')->after('reading_time_minutes');
            $table->string('comments_moderation', 20)->default('inherit')->after('comments_status');
        });

        Schema::table('shop_products', function (Blueprint $table) {
            $table->string('comments_status', 20)->default('inherit')->after('sort_order');
            $table->string('comments_moderation', 20)->default('inherit')->after('comments_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('comments_banned_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('comments_banned_at');
        });

        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn(['comments_status', 'comments_moderation']);
        });

        Schema::table('cms_posts', function (Blueprint $table) {
            $table->dropColumn(['comments_status', 'comments_moderation']);
        });

        Schema::dropIfExists('comments');
    }
};
