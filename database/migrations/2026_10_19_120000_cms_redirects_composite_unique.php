<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_redirects')) {
            return;
        }

        if (Schema::hasColumn('cms_redirects', 'from_query')) {
            DB::table('cms_redirects')->whereNull('from_query')->update(['from_query' => '']);
        }

        try {
            Schema::table('cms_redirects', function (Blueprint $table) {
                $table->dropUnique(['from_path']);
            });
        } catch (\Throwable) {
            // Index may already be gone on some environments.
        }

        try {
            Schema::table('cms_redirects', function (Blueprint $table) {
                $table->unique(['from_path', 'from_query'], 'cms_redirects_from_path_from_query_unique');
            });
        } catch (\Throwable) {
            // Composite unique may already exist.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cms_redirects')) {
            return;
        }

        try {
            Schema::table('cms_redirects', function (Blueprint $table) {
                $table->dropUnique('cms_redirects_from_path_from_query_unique');
            });
        } catch (\Throwable) {
            // ignore
        }

        try {
            Schema::table('cms_redirects', function (Blueprint $table) {
                $table->unique('from_path');
            });
        } catch (\Throwable) {
            // ignore
        }
    }
};
