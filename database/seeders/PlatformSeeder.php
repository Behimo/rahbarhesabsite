<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\ShopProduct;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 ۱. Admin کاربر
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@rahbarhesab.ir'],
            [
                'name' => 'مدیر اصلی',
                'mobile' => PhoneNormalizer::toLocal('09120000000'),
                'phone' => PhoneNormalizer::toLocal('09120000000'),
                'password' => 'secret123',
                'role' => User::ROLE_ADMIN,
                'status' => 'active',
            ]
        );

        // 🔹 ۲. کاربر نمونه
        $demoUser = User::query()->updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'کاربر نمونه',
                'mobile' => PhoneNormalizer::toLocal('09121111111'),
                'phone' => PhoneNormalizer::toLocal('09121111111'),
                'password' => 'password',
                'role' => User::ROLE_USER,
                'status' => 'active',
            ]
        );

        // 🔹 ۳. مدرس نمونه
        $instructor = User::query()->updateOrCreate(
            ['email' => 'instructor@example.com'],
            [
                'name' => 'مدرس نمونه',
                'password' => 'password',
                'role' => User::ROLE_INSTRUCTOR,
                'status' => 'active',
            ]
        );

        // 🔹 ۴. دوره رایگان
        $product = ShopProduct::query()->updateOrCreate(
            ['slug' => 'laravel-basics'],
            [
                'title' => 'آموزش Laravel از صفر',
                'subtitle' => 'دوره جامع برای شروع برنامه‌نویسی وب',
                'description' => 'در این دوره با فریمورک Laravel آشنا می‌شوید و یک پروژه واقعی می‌سازید.',
                'price' => 0,
                'type' => ShopProduct::TYPE_COURSE,
                'is_published' => false,
                'sort_order' => 99,
            ]
        );

        $course = Course::query()->updateOrCreate(
            ['shop_product_id' => $product->id],
            [
                'instructor_id' => $instructor->id,
                'level' => 'beginner',
                'duration_minutes' => 120,
                'what_you_learn' => ['نصب Laravel', 'ساخت CRUD', 'احراز هویت'],
                'requirements' => ['آشنایی با PHP'],
            ]
        );

        $section = CourseSection::query()->updateOrCreate(
            ['course_id' => $course->id, 'title' => 'مقدمات'],
            ['sort_order' => 1]
        );

        CourseLesson::query()->updateOrCreate(
            ['section_id' => $section->id, 'slug' => 'intro'],
            [
                'title' => 'معرفی دوره',
                'content' => '<p>به دوره Laravel خوش آمدید!</p>',
                'is_free_preview' => true,
                'sort_order' => 1,
            ]
        );

        CourseLesson::query()->updateOrCreate(
            ['section_id' => $section->id, 'slug' => 'install'],
            [
                'title' => 'نصب Laravel',
                'content' => '<p>نحوه نصب Laravel با Composer</p>',
                'sort_order' => 2,
            ]
        );

        // 🔹 ۵. دوره پولی و به‌روزرسانی لایسنس اسپات‌پلیر
        $paidProduct = ShopProduct::query()->updateOrCreate(
            ['slug' => 'advanced-laravel'],
            [
                'title' => 'Laravel پیشرفته',
                'subtitle' => 'معماری، تست و بهینه‌سازی',
                'description' => 'دوره پیشرفته برای توسعه‌دهندگان با تجربه.',
                'price' => 990000,
                'type' => ShopProduct::TYPE_COURSE,
                'is_published' => false,
                'sort_order' => 100,
            ]
        );

        Course::query()->updateOrCreate(
            ['shop_product_id' => $paidProduct->id],
            [
                'instructor_id' => $instructor->id,
                'level' => 'advanced',
                'duration_minutes' => 300,
                'what_you_learn' => ['Repository Pattern', 'Event Sourcing', 'Performance'],
            ]
        );

        // 🔹 ۶. ثبت‌نام رایگان کاربر نمونه در دوره رایگان
        $demoUser->enrollments()->firstOrCreate(
            ['course_id' => $course->id],
            [
                'order_id' => null,
                'enrolled_at' => now(),
                'status' => 'active',
                'source' => 'free',
                'progress_percent' => 0,
            ]
        );

        // 🔹 ۷. کد تخفیف نمونه (تست سیستم کوپن)
        Coupon::query()->updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'title' => 'تخفیف خوش‌آمدگویی ۱۰٪',
                'type' => 'percentage_cart',
                'value' => 10,
                'max_discount_amount' => 200000,
                'min_order_amount' => 500000,
                'usage_limit_total' => 100,
                'usage_limit_per_user' => 1,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ]
        );
    }
}