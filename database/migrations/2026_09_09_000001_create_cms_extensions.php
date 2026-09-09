<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_admins')) {
            Schema::table('cms_admins', function (Blueprint $table) {
                if (! Schema::hasColumn('cms_admins', 'role')) {
                    $table->string('role')->default('admin')->after('password');
                }
                if (! Schema::hasColumn('cms_admins', 'permissions')) {
                    $table->json('permissions')->nullable()->after('role');
                }
                if (! Schema::hasColumn('cms_admins', 'is_super')) {
                    $table->boolean('is_super')->default(false)->after('permissions');
                }
            });
        }

        if (Schema::hasTable('cms_pages')) {
            Schema::table('cms_pages', function (Blueprint $table) {
                if (! Schema::hasColumn('cms_pages', 'builder_enabled')) {
                    $table->boolean('builder_enabled')->default(false)->after('template');
                }
                if (! Schema::hasColumn('cms_pages', 'builder_content')) {
                    $table->json('builder_content')->nullable()->after('content');
                }
                if (! Schema::hasColumn('cms_pages', 'status')) {
                    $table->string('status')->default('published')->after('is_published');
                }
                if (! Schema::hasColumn('cms_pages', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('status');
                }
                if (! Schema::hasColumn('cms_pages', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        if (Schema::hasTable('cms_posts')) {
            Schema::table('cms_posts', function (Blueprint $table) {
                if (! Schema::hasColumn('cms_posts', 'status')) {
                    $table->string('status')->default('published')->after('is_published');
                }
                if (! Schema::hasColumn('cms_posts', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        if (Schema::hasTable('course_lessons')) {
            Schema::table('course_lessons', function (Blueprint $table) {
                if (! Schema::hasColumn('course_lessons', 'download_url')) {
                    $table->string('download_url')->nullable()->after('video_url');
                }
                if (! Schema::hasColumn('course_lessons', 'spotplayer_course_id')) {
                    $table->string('spotplayer_course_id')->nullable()->after('video_provider');
                }
                if (! Schema::hasColumn('course_lessons', 'spotplayer_item_id')) {
                    $table->string('spotplayer_item_id')->nullable()->after('spotplayer_course_id');
                }
            });
        }

        if (Schema::hasTable('courses') && ! Schema::hasColumn('courses', 'spotplayer_course_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('spotplayer_course_id')->nullable()->after('requirements');
            });
        }

        if (! Schema::hasTable('cms_audit_logs')) {
            Schema::create('cms_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_id')->nullable()->constrained('cms_admins')->nullOnDelete();
                $table->string('action');
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('payload')->nullable();
                $table->string('ip')->nullable();
                $table->timestamps();
                $table->index(['subject_type', 'subject_id']);
            });
        }

        if (! Schema::hasTable('cms_themes')) {
            Schema::create('cms_themes', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('version')->default('1.0.0');
                $table->json('manifest')->nullable();
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cms_taxonomies')) {
            Schema::create('cms_taxonomies', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('type')->default('category');
                $table->json('object_types')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cms_taxonomy_terms')) {
            Schema::create('cms_taxonomy_terms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('taxonomy_id')->constrained('cms_taxonomies')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('cms_taxonomy_terms')->nullOnDelete();
                $table->string('slug');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['taxonomy_id', 'slug']);
            });
        }

        if (! Schema::hasTable('cms_termables')) {
            Schema::create('cms_termables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('term_id')->constrained('cms_taxonomy_terms')->cascadeOnDelete();
                $table->morphs('termable');
                $table->timestamps();
                $table->unique(['term_id', 'termable_type', 'termable_id']);
            });
        }

        if (! Schema::hasTable('cms_menus')) {
            Schema::create('cms_menus', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('location')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cms_menu_items')) {
            Schema::create('cms_menu_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('menu_id')->constrained('cms_menus')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('cms_menu_items')->nullOnDelete();
                $table->string('label');
                $table->string('type')->default('custom');
                $table->string('url')->nullable();
                $table->string('route_name')->nullable();
                $table->json('route_params')->nullable();
                $table->string('target')->default('_self');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cms_page_revisions')) {
            Schema::create('cms_page_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained('cms_pages')->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('cms_admins')->nullOnDelete();
                $table->json('content')->nullable();
                $table->json('builder_content')->nullable();
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cms_blocks')) {
            Schema::create('cms_blocks', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('type');
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('spotplayer_licenses')) {
            Schema::create('spotplayer_licenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('license_key')->nullable();
                $table->string('status')->default('pending');
                $table->json('api_response')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'course_id']);
            });
        }

        if (! Schema::hasTable('cms_redirects')) {
            Schema::create('cms_redirects', function (Blueprint $table) {
                $table->id();
                $table->string('from_path')->unique();
                $table->string('to_path');
                $table->unsignedSmallInteger('status_code')->default(301);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('cms_redirects');
        Schema::dropIfExists('spotplayer_licenses');
        Schema::dropIfExists('cms_blocks');
        Schema::dropIfExists('cms_page_revisions');
        Schema::dropIfExists('cms_menu_items');
        Schema::dropIfExists('cms_menus');
        Schema::dropIfExists('cms_termables');
        Schema::dropIfExists('cms_taxonomy_terms');
        Schema::dropIfExists('cms_taxonomies');
        Schema::dropIfExists('cms_themes');
        Schema::dropIfExists('cms_audit_logs');
    }
};
