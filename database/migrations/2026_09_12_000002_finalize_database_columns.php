<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ۱. تکمیل جدول spotplayer_licenses
        Schema::table('spotplayer_licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('spotplayer_licenses', 'spot_url')) {
                $table->string('spot_url')->nullable()->after('license_key')->comment('لینک مستقیم باز کردن در اپ اسپات پلیر');
            }
            if (! Schema::hasColumn('spotplayer_licenses', 'device_count')) {
                $table->unsignedTinyInteger('device_count')->default(0)->after('spot_url')->comment('تعداد دستگاه‌های فعال شده');
            }
            if (! Schema::hasColumn('spotplayer_licenses', 'devices_limit')) {
                $table->unsignedTinyInteger('devices_limit')->default(2)->after('device_count')->comment('سقف مجاز دستگاه‌ها');
            }
        });

        // ۲. تکمیل جدول course_enrollments
        Schema::table('course_enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('course_enrollments', 'status')) {
                $table->enum('status', ['active', 'expired', 'revoked'])->default('active')->after('course_id');
            }
            if (! Schema::hasColumn('course_enrollments', 'source')) {
                $table->enum('source', ['purchase', 'free', 'gift', 'manual'])->default('purchase')->after('status');
            }
        });

        // ۳. تکمیل جدول payments
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('order_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'error_message')) {
                $table->string('error_message', 255)->nullable()->after('gateway_response');
            }
            if (! Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('error_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('spotplayer_licenses', function (Blueprint $table) {
            $table->dropColumn(['spot_url', 'device_count', 'devices_limit']);
        });
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn(['status', 'source']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'error_message', 'paid_at']);
        });
    }
};
