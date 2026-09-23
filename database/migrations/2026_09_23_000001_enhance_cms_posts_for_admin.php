<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_posts', 'featured_image_alt')) {
                $table->string('featured_image_alt')->nullable()->after('featured_image');
            }
            if (! Schema::hasColumn('cms_posts', 'reading_time_minutes')) {
                $table->unsignedSmallInteger('reading_time_minutes')->nullable()->after('views');
            }
        });

        if (! Schema::hasTable('cms_post_revisions')) {
            Schema::create('cms_post_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained('cms_posts')->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('cms_admins')->nullOnDelete();
                $table->json('snapshot');
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_post_revisions');

        Schema::table('cms_posts', function (Blueprint $table) {
            if (Schema::hasColumn('cms_posts', 'featured_image_alt')) {
                $table->dropColumn('featured_image_alt');
            }
            if (Schema::hasColumn('cms_posts', 'reading_time_minutes')) {
                $table->dropColumn('reading_time_minutes');
            }
        });
    }
};
