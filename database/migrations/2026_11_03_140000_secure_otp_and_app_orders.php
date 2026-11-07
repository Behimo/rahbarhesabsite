<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('purpose', 32)->default('web_login')->after('phone');
            $table->string('challenge_id', 64)->nullable()->unique()->after('purpose');
            $table->index(['phone', 'purpose']);
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('otp_verifications', function (Blueprint $table) {
                $table->text('code')->change();
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('channel', 16)->default('web')->after('status');
            $table->string('idempotency_key', 80)->nullable()->after('channel');
            $table->unique(['user_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'idempotency_key']);
            $table->dropColumn(['channel', 'idempotency_key']);
        });

        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropUnique(['challenge_id']);
            $table->dropIndex(['phone', 'purpose']);
        });

        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropColumn(['purpose', 'challenge_id']);
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('otp_verifications', function (Blueprint $table) {
                $table->string('code', 10)->change();
            });
        }
    }
};
