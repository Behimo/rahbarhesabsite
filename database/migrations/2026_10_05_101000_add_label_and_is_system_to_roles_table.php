<?php

use App\Models\Role;
use App\Support\AccessCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('label')->nullable()->after('name');
            $table->boolean('is_system')->default(false)->after('guard_name');
        });

        foreach (AccessCatalog::roles() as $name => $label) {
            Role::query()
                ->where('name', $name)
                ->where('guard_name', AccessCatalog::guard())
                ->update([
                    'label' => $label,
                    'is_system' => true,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['label', 'is_system']);
        });
    }
};
