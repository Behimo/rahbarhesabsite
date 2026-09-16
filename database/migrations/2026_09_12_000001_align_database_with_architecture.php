<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ۱. ارتقای جدول users برای مهاجرت وردپرس و پسورد Phpass
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'wp_id')) {
                $table->unsignedBigInteger('wp_id')->nullable()->unique()->after('id')->comment('شناسه کاربر وردپرس');
            }
            if (! Schema::hasColumn('users', 'mobile')) {
                $table->string('mobile', 15)->nullable()->unique()->after('wp_id')->comment('شماره موبایل استاندارد 09XXXXXXXXX');
            }
            if (! Schema::hasColumn('users', 'is_wp_password')) {
                $table->boolean('is_wp_password')->default(false)->after('password')->comment('فلگ هش Phpass وردپرس');
            }
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 100)->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 100)->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'banned', 'suspended'])->default('active')->after('is_wp_password');
            }
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
        });

        // ۲. جدول لاگ امنیتی ارسال و تایید OTP
        if (! Schema::hasTable('otp_logs')) {
            Schema::create('otp_logs', function (Blueprint $table) {
                $table->id();
                $table->string('mobile', 15)->index();
                $table->string('ip_address', 45)->index();
                $table->text('user_agent')->nullable();
                $table->enum('type', ['login', 'register', 'password_reset', 'change_phone'])->default('login');
                $table->timestamp('sent_at')->useCurrent();
                $table->boolean('is_used')->default(false);
                $table->unsignedTinyInteger('attempts')->default(0);

                $table->index(['mobile', 'sent_at'], 'idx_otp_mobile_sent');
                $table->index(['ip_address', 'sent_at'], 'idx_otp_ip_sent');
            });
        }

        // ۳. جداول سیستم پیشرفته کدهای تخفیف (Coupons)
        if (! Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique()->comment('کد تخفیف');
                $table->string('title')->nullable()->comment('عنوان یا مناسبت تخفیف');
                $table->enum('type', ['percentage_cart', 'fixed_cart', 'percentage_product', 'fixed_product'])->default('percentage_cart');
                $table->decimal('value', 14, 2)->comment('مقدار درصد یا مبلغ تخفیف به تومان');
                $table->decimal('max_discount_amount', 14, 2)->nullable()->comment('حداکثر سقف مبلغ تخفیف');
                $table->decimal('min_order_amount', 14, 2)->nullable()->comment('حداقل مبلغ مجاز سبد خرید');
                $table->decimal('max_order_amount', 14, 2)->nullable()->comment('حداکثر مبلغ مجاز سبد خرید');
                $table->boolean('is_first_order_only')->default(false)->comment('فقط برای اولین خرید');
                $table->boolean('exclude_sale_items')->default(false)->comment('عدم اعمال روی محصولات حراجی');
                $table->unsignedInteger('usage_limit_total')->nullable()->comment('سقف مصرف کل');
                $table->unsignedInteger('usage_limit_per_user')->default(1)->comment('سقف مصرف به ازای هر کاربر');
                $table->unsignedInteger('used_count')->default(0)->comment('تعداد دفعات استفاده‌شده');
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['code', 'is_active'], 'idx_coupons_code_active');
                $table->index(['starts_at', 'expires_at'], 'idx_coupons_dates');
            });
        }

        if (! Schema::hasTable('coupon_targets')) {
            Schema::create('coupon_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
                $table->enum('target_type', ['product', 'user', 'category'])->comment('نوع هدف');
                $table->unsignedBigInteger('target_id')->comment('شناسه در جدول مربوطه');
                $table->boolean('is_excluded')->default(false)->comment('false = فقط این، true = به جز این');
                $table->timestamps();

                $table->unique(['coupon_id', 'target_type', 'target_id', 'is_excluded'], 'uq_coupon_target');
                $table->index(['target_type', 'target_id'], 'idx_target_lookup');
            });
        }

        if (! Schema::hasTable('coupon_usages')) {
            Schema::create('coupon_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->decimal('discount_amount', 14, 2)->comment('مبلغ تخفیف اعمال شده به تومان');
                $table->timestamp('used_at')->useCurrent();

                $table->unique(['coupon_id', 'order_id'], 'uq_coupon_order');
                $table->index(['coupon_id', 'user_id'], 'idx_coupon_user_count');
            });
        }

        // ۴. جدول آیتم‌های پکیج و باندل دوره‌ها
        if (! Schema::hasTable('course_bundle_items')) {
            Schema::create('course_bundle_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_product_id')->constrained('shop_products')->cascadeOnDelete()->comment('محصول والد پکیج');
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete()->comment('دوره اتمیک زیرمجموعه');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['shop_product_id', 'course_id'], 'uq_bundle_course');
            });
        }

        // ۵. ارتقای جدول سفارشات (orders)
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'wp_id')) {
                $table->unsignedBigInteger('wp_id')->nullable()->unique()->after('id')->comment('شناسه سفارش وردپرس');
            }
            if (! Schema::hasColumn('orders', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->after('status')->constrained('coupons')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code', 50)->nullable()->after('coupon_id');
            }
            if (! Schema::hasColumn('orders', 'user_ip')) {
                $table->string('user_ip', 45)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('orders', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('user_ip');
            }
        });

        // ۶. ارتقای جدول اقلام سفارش (order_items)
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'metadata')) {
                $table->json('metadata')->nullable()->after('quantity')->comment('اسنپ‌شات دوره‌ها یا اطلاعات لحظه خرید');
            }
        });

        // ۷. ارتقای جدول پرداخت‌ها (payments)
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'card_pan')) {
                $table->string('card_pan', 30)->nullable()->after('ref_id')->comment('شماره کارت ماسک شده خریدار');
            }
            if (! Schema::hasColumn('payments', 'card_hash')) {
                $table->string('card_hash', 64)->nullable()->after('card_pan')->comment('هش شاپرکی کارت');
            }
            if (! Schema::hasColumn('payments', 'gateway_payload')) {
                $table->json('gateway_payload')->nullable()->after('card_hash');
            }
            if (! Schema::hasColumn('payments', 'gateway_response')) {
                $table->json('gateway_response')->nullable()->after('gateway_payload');
            }
            if (! Schema::hasColumn('payments', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('gateway_response');
            }
        });

        // ۸. ارتقای جدول لایسنس اسپات‌پلیر (spotplayer_licenses)
        Schema::table('spotplayer_licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('spotplayer_licenses', 'spot_license_id')) {
                $table->string('spot_license_id', 100)->nullable()->after('course_id')->comment('شناسه _id در وب‌سرویس اسپات‌پلیر');
            }
            if (! Schema::hasColumn('spotplayer_licenses', 'issued_via')) {
                $table->enum('issued_via', ['purchase', 'manual', 'lazy'])->default('purchase')->after('status');
            }
            if (! Schema::hasColumn('spotplayer_licenses', 'last_verified_at')) {
                $table->timestamp('last_verified_at')->nullable()->after('issued_via');
            }
        });
    }

    public function down(): void
    {
        // عملیات بازگشت در صورت نیاز
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupon_targets');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('course_bundle_items');
        Schema::dropIfExists('otp_logs');
    }
};
