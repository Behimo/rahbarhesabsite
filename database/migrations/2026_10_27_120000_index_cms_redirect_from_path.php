<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // On MySQL the unique key is a hash of path+query, so HandleCmsRedirects' from_path lookup needs its own index.
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('cms_redirects', function (Blueprint $table) {
            $table->index('from_path', 'cms_redirects_from_path_index');
        });
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('cms_redirects', function (Blueprint $table) {
            $table->dropIndex('cms_redirects_from_path_index');
        });
    }
};
