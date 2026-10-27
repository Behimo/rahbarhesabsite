<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_redirects', function (Blueprint $table) {
            $table->dropUnique('cms_redirects_from_path_from_query_unique');
            $table->string('from_path', 500)->change();
            $table->string('to_path', 500)->change();
        });
        Schema::table('cms_redirects', function (Blueprint $table) {
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                // Hash the complete identity: a prefix index would reject distinct long URLs.
                $table->char('from_identity', 64)->storedAs("SHA2(CONCAT(from_path, CHAR(0), COALESCE(from_query, '')), 256)");
                $table->unique('from_identity', 'cms_redirects_from_identity_unique');
            } else {
                $table->unique(['from_path', 'from_query'], 'cms_redirects_from_path_from_query_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_redirects', function (Blueprint $table) {
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->dropUnique('cms_redirects_from_identity_unique');
                $table->dropColumn('from_identity');
            } else {
                $table->dropUnique('cms_redirects_from_path_from_query_unique');
            }
        });
        Schema::table('cms_redirects', function (Blueprint $table) {
            $table->string('from_path', 191)->change();
            $table->string('to_path', 191)->change();
            $table->unique(['from_path', 'from_query'], 'cms_redirects_from_path_from_query_unique');
        });
    }
};
