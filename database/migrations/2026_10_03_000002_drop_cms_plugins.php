<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cms_plugins');

        foreach (['users', 'cms_admins'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'permissions')) {
                continue;
            }

            DB::table($table)->whereNotNull('permissions')->orderBy('id')->each(function (object $row) use ($table) {
                $permissions = json_decode($row->permissions, true);

                if (! is_array($permissions)) {
                    return;
                }

                $filtered = array_values(array_filter(
                    $permissions,
                    fn ($permission) => $permission !== 'manage_plugins'
                ));

                if ($filtered === array_values($permissions)) {
                    return;
                }

                DB::table($table)->where('id', $row->id)->update([
                    'permissions' => json_encode($filtered),
                ]);
            });
        }

        if (Schema::hasTable('cms_audit_logs')) {
            DB::table('cms_audit_logs')->where(function ($query) {
                $query->where('action', 'like', 'plugin.%')
                    ->orWhere('subject_type', 'App\\Models\\CmsPlugin');
            })->delete();
        }
    }

    public function down(): void
    {
        // Plugin management was removed from the site.
    }
};
