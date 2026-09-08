<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsSetting;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Database\Seeder;

class RahbarHesabSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'contact_email' => 'info@rahbarhesab.com',
            'contact_phone' => '021-91009900',
            'site_tagline' => 'خالق رهبران حسابداری',
        ];

        foreach ($settings as $key => $value) {
            CmsSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $instructor = User::query()->updateOrCreate(
            ['phone' => '09120000001'],
            [
                'name' => 'مرتضی رهبر',
                'email' => 'instructor@rahbarhesab.com',
                'password' => 'password',
                'role' => User::ROLE_INSTRUCTOR,
            ]
        );

        $courses = [
            [
                'slug' => 'ezdevaj-maliati-hoghooghi',
                'title' => 'دوره آموزش اظهارنامه عملکرد حقوقی',
                'subtitle' => '۸ ساعت آموزش کاربردی',
                'description' => 'آموزش کامل اظهارنامه عملکرد حقوقی از صفر تا صد با تمرکز بر بازار کار ایران.',
                'price' => 2200000,
                'duration' => 480,
                'level' => 'intermediate',
            ],
            [
                'slug' => 'ezdevaj-arzesh-afzoodeh',
                'title' => 'دوره آموزش اظهارنامه ارزش افزوده',
                'subtitle' => '۷ ساعت',
                'description' => 'آموزش جامع اظهارنامه مالیات بر ارزش افزوده.',
                'price' => 1370000,
                'duration' => 420,
                'level' => 'intermediate',
            ],
            [
                'slug' => 'hoghogh-dastmozd-1405',
                'title' => 'دوره آموزش حقوق و دستمزد 1405',
                'subtitle' => '۱۰ ساعت',
                'description' => 'آموزش کامل حقوق و دستمزد سال ۱۴۰۵.',
                'price' => 3300000,
                'duration' => 600,
                'level' => 'beginner',
            ],
            [
                'slug' => 'hazine-haml-maliati',
                'title' => 'آموزش رایگان هزینه حمل مواد مستقیم در اظهارنامه',
                'subtitle' => '۱ ساعت — رایگان',
                'description' => 'پاسخ سوال پرتکرار حسابداری درباره هزینه حمل مواد مستقیم.',
                'price' => 0,
                'duration' => 60,
                'level' => 'beginner',
            ],
        ];

        foreach ($courses as $i => $data) {
            $product = ShopProduct::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'subtitle' => $data['subtitle'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'type' => ShopProduct::TYPE_COURSE,
                    'is_published' => true,
                    'sort_order' => $i + 1,
                    'meta_title' => $data['title'].' | راهبر حساب',
                    'meta_description' => $data['description'],
                ]
            );

            $course = Course::query()->updateOrCreate(
                ['shop_product_id' => $product->id],
                [
                    'instructor_id' => $instructor->id,
                    'level' => $data['level'],
                    'duration_minutes' => $data['duration'],
                    'what_you_learn' => ['آموزش عملی', 'پشتیبانی تخصصی', 'مدرک معتبر'],
                ]
            );

            $section = CourseSection::query()->updateOrCreate(
                ['course_id' => $course->id, 'title' => 'فصل اول'],
                ['sort_order' => 1]
            );

            CourseLesson::query()->updateOrCreate(
                ['section_id' => $section->id, 'slug' => 'intro'],
                [
                    'title' => 'معرفی دوره',
                    'content' => '<p>به دوره '.$data['title'].' خوش آمدید.</p>',
                    'is_free_preview' => $data['price'] === 0,
                    'sort_order' => 1,
                ]
            );
        }

        CmsPage::query()->updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'راهبر حساب | موسسه آموزش حسابداری',
                'meta_title' => 'راهبر حساب | موسسه آموزش حسابداری و خدمات مالی',
                'meta_description' => 'موسسه آموزش حسابداری راهبر حساب — دوره‌های کاربردی حسابداری و مالیات، خدمات مالی و مالیاتی.',
                'meta_keywords' => 'راهبر حساب, آموزش حسابداری, مالیات, اظهارنامه, حسابداری',
                'is_published' => true,
                'is_system' => true,
            ]
        );
    }
}
