<?php

use App\Models\User;
use App\Support\AccessCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        AccessCatalog::install();
        $this->importCmsAdmins();
        $this->migrateLegacyUserRoles();

        foreach (['cms_post_revisions', 'cms_page_revisions', 'cms_audit_logs'] as $table) {
            $this->retargetAdminForeignKey($table);
        }

        Schema::dropIfExists('cms_admins');

        $drops = array_values(array_filter(
            ['permissions', 'role'],
            fn (string $column) => Schema::hasColumn('users', $column)
        ));

        if ($drops !== []) {
            Schema::table('users', function (Blueprint $table) use ($drops) {
                $table->dropColumn($drops);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cms_admins')) {
            Schema::create('cms_admins', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role')->default('admin');
                $table->json('permissions')->nullable();
                $table->boolean('is_super')->default(false);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user');
            }
            if (! Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable();
            }
        });
    }

    private function importCmsAdmins(): void
    {
        if (! Schema::hasTable('cms_admins')) {
            return;
        }

        foreach (DB::table('cms_admins')->orderBy('id')->get() as $admin) {
            $roleName = ! empty($admin->is_super) ? AccessCatalog::ROLE_ADMIN : ($admin->role ?? AccessCatalog::ROLE_ADMIN);
            if (! array_key_exists($roleName, AccessCatalog::roles())) {
                $roleName = AccessCatalog::ROLE_ADMIN;
            }

            $userId = DB::table('users')->where('email', $admin->email)->value('id');

            if (! $userId) {
                $userId = DB::table('users')->insertGetId([
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'password' => $admin->password,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            User::query()->find($userId)?->syncRoles([$roleName]);
        }
    }

    private function migrateLegacyUserRoles(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        foreach (DB::table('users')->orderBy('id')->get(['id', 'role']) as $row) {
            $user = User::query()->find($row->id);

            if (! $user || $user->roles()->exists()) {
                continue;
            }

            $roleName = array_key_exists((string) $row->role, AccessCatalog::roles())
                ? (string) $row->role
                : AccessCatalog::ROLE_USER;

            $user->syncRoles([$roleName]);
        }
    }

    private function retargetAdminForeignKey(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'admin_id')) {
            return;
        }

        $existing = collect(Schema::getForeignKeys($table))->first(
            fn (array $key) => in_array('admin_id', $key['columns'] ?? [], true)
        );

        if ($existing) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['admin_id']);
            });
        }

        DB::table($table)->whereNotNull('admin_id')->update(['admin_id' => null]);

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
