<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasColumn('users', 'status')) {
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'banned', 'suspended', 'pending_activation') NOT NULL DEFAULT 'active'");
            } else {
                // SQLite enum CHECK must be widened; store as plain string.
                Schema::table('users', function (Blueprint $table) {
                    $table->string('status', 32)->default('active')->change();
                });
            }
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'claimed_mobile')) {
                $table->string('claimed_mobile', 15)->nullable()->after('mobile');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE orders MODIFY user_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            if (! Schema::hasColumn('orders', 'needs_review')) {
                $table->boolean('needs_review')->default(false)->after('wp_id');
                $table->index('needs_review');
            }
            if (! Schema::hasColumn('orders', 'wp_customer_id')) {
                $table->unsignedBigInteger('wp_customer_id')->nullable()->after('needs_review');
            }
            if (! Schema::hasColumn('orders', 'billing_phone')) {
                $table->string('billing_phone', 20)->nullable()->after('wp_customer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'billing_phone')) {
                $table->dropColumn('billing_phone');
            }
            if (Schema::hasColumn('orders', 'wp_customer_id')) {
                $table->dropColumn('wp_customer_id');
            }
            if (Schema::hasColumn('orders', 'needs_review')) {
                $table->dropIndex(['needs_review']);
                $table->dropColumn('needs_review');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::table('orders')->whereNull('user_id')->delete();

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE orders MODIFY user_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'claimed_mobile')) {
                $table->dropColumn('claimed_mobile');
            }
        });

        DB::table('users')->where('status', 'pending_activation')->update(['status' => 'suspended']);

        if (Schema::hasColumn('users', 'status') && Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'banned', 'suspended') NOT NULL DEFAULT 'active'");
        }
    }
};
