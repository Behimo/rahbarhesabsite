<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\Seeder;

/**
 * دسته‌های واقعی سایت، هم‌تراز با اسلاگ‌هایی که منوی اصلی به آن‌ها لینک می‌دهد.
 * اجرای دوباره امن است: دستهٔ موجود به‌روز می‌شود و دستهٔ حذف‌شده برمی‌گردد.
 */
class SiteCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->seed(Category::TYPE_PRODUCT, [
            ['accountants-choice', 'بیشترین انتخاب حسابداران', 'دوره‌هایی که حسابداران بیشتر انتخاب می‌کنند'],
            ['introductory-accounting-course', 'آموزش های حسابداری بازار کار', 'آموزش‌های حسابداری کاربردی برای ورود به بازار کار'],
            ['advanced-accounting-course', 'آموزش های مالیاتی بازار کار', 'آموزش‌های مالیاتی موردنیاز بازار کار'],
            ['contractor-accounting-training-courses', 'آموزش حسابداری پیمانکاری', 'حسابداری قراردادها و پروژه‌های پیمانکاری'],
            ['import-export-accounting-training-courses', 'آموزش حسابداری واردات و صادرات', 'حسابداری عملیات واردات و صادرات'],
            ['cost-accounting-training-courses', 'آموزش حسابداری صنعتی، بهای تمام شده', 'حسابداری صنعتی و محاسبه بهای تمام شده'],
            ['free-accounting-course', 'آموزشهای هدیه (رایگان)', 'آموزش‌های رایگان حسابداری'],
        ]);

        $this->seed(Category::TYPE_POST, [
            ['blogs', 'مقالات آموزش حسابداری', 'مقالات آموزشی حسابداری'],
            ['news', 'اخبار حسابداری', 'اخبار و اطلاعیه‌های حسابداری'],
            ['sample-questions', 'نمونه سوالات', 'نمونه سوالات حسابداری و مالیات'],
        ]);

        app(CategoryService::class)->forget();

        $this->command?->info('Site categories synced with the primary menu.');
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $items
     */
    private function seed(string $type, array $items): void
    {
        foreach ($items as $index => [$slug, $name, $description]) {
            $category = Category::withTrashed()->updateOrCreate(
                [
                    'type' => $type,
                    'parent_id' => null,
                    'slug' => $slug,
                ],
                [
                    'name' => $name,
                    'description' => $description,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );

            if ($category->trashed()) {
                $category->restore();
            }
        }
    }
}
