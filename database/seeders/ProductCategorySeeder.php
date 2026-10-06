<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\ShopProduct;
use Database\Seeders\Support\SampleInstructor;
use Illuminate\Database\Seeder;

/**
 * نمونهٔ درخت دستهٔ محصول: دوره، نرم‌افزار و کارت‌خوان روی یک درخت، با نوع جدا روی خود محصول.
 */
class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('ProductCategorySeeder در production اجرا نشد.');

            return;
        }

        $accounting = $this->category('accounting', 'حسابداری', null, 1, 'آموزش و ابزار حسابداری');
        $tax = $this->category('tax', 'مالیات', $accounting, 1, 'اظهارنامه و ارزش افزوده');
        $payroll = $this->category('payroll', 'حقوق و دستمزد', $accounting, 2, 'محاسبه و ارسال لیست بیمه');

        $software = $this->category('software', 'نرم‌افزار', null, 2, 'نرم‌افزارهای مالی');
        $holoo = $this->category('holoo', 'هلو', $software, 1, 'نسخه‌های حسابداری هلو');

        $hardware = $this->category('hardware', 'تجهیزات', null, 3, 'سخت‌افزار فروش');
        $pos = $this->category('pos', 'کارت‌خوان', $hardware, 1, 'دستگاه کارت‌خوان');

        $this->call(RolesAndPermissionsSeeder::class);

        $instructor = SampleInstructor::findOrCreate()
            ?? throw new \RuntimeException('مدرس نمونه ساخته نشد.');

        $taxCourse = $this->product('demo-tax-return-course', [
            'title' => 'دوره اظهارنامه مالیاتی',
            'subtitle' => 'ثبت و ارسال اظهارنامه برای مشاغل',
            'description' => 'دورهٔ ویدیویی اظهارنامه. نوع محصول course است و دستهٔ اصلی‌اش مالیات.',
            'price' => 2400000,
            'sale_price' => 1980000,
            'type' => ShopProduct::TYPE_COURSE,
            'sort_order' => 1,
        ]);
        Course::query()->updateOrCreate(
            ['shop_product_id' => $taxCourse->id],
            [
                'instructor_id' => $instructor->id,
                'level' => 'intermediate',
                'duration_minutes' => 480,
                'what_you_learn' => ['تکمیل اظهارنامه', 'مهلت‌های قانونی', 'خطاهای رایج'],
                'requirements' => ['آشنایی مقدماتی با حسابداری'],
            ]
        );
        $taxCourse->syncCategories([$tax->id, $accounting->id], $tax->id);

        $payrollCourse = $this->product('demo-payroll-course', [
            'title' => 'دوره حقوق و دستمزد',
            'subtitle' => 'از حکم تا لیست بیمه',
            'description' => 'دورهٔ دوم، فقط در شاخهٔ حقوق و دستمزد.',
            'price' => 1800000,
            'type' => ShopProduct::TYPE_COURSE,
            'sort_order' => 2,
        ]);
        Course::query()->updateOrCreate(
            ['shop_product_id' => $payrollCourse->id],
            [
                'instructor_id' => $instructor->id,
                'level' => 'beginner',
                'duration_minutes' => 360,
                'what_you_learn' => ['محاسبه حقوق', 'لیست بیمه'],
                'requirements' => [],
            ]
        );
        $payrollCourse->syncCategories([$payroll->id], $payroll->id);

        $holooProduct = $this->product('demo-holoo-software', [
            'title' => 'نرم‌افزار حسابداری هلو',
            'subtitle' => 'لایسنس نسخهٔ تولیدی',
            'description' => 'محصول دیجیتال. در صفحهٔ دوره‌ها نمی‌آید چون نوعش course نیست.',
            'price' => 8500000,
            'type' => ShopProduct::TYPE_DIGITAL,
            'sort_order' => 3,
        ]);
        $holooProduct->syncCategories([$holoo->id, $software->id], $holoo->id);

        $reader = $this->product('demo-pos-terminal', [
            'title' => 'کارت‌خوان فروشگاهی',
            'subtitle' => 'ارسال و گارانتی',
            'description' => 'محصول فیزیکی. دستهٔ اصلی‌اش کارت‌خوان است.',
            'price' => 4200000,
            'type' => ShopProduct::TYPE_PHYSICAL,
            'stock' => 12,
            'sort_order' => 4,
        ]);
        $reader->syncCategories([$pos->id], $pos->id);

        $this->command?->info('Product category sample ready: courses, software, and a card reader on one tree.');
    }

    private function category(string $slug, string $name, ?Category $parent, int $sort, string $description): Category
    {
        return Category::query()->updateOrCreate(
            [
                'type' => Category::TYPE_PRODUCT,
                'parent_id' => $parent?->id,
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'description' => $description,
                'sort_order' => $sort,
                'is_active' => true,
            ]
        );
    }

    /** @param  array<string, mixed>  $attributes */
    private function product(string $slug, array $attributes): ShopProduct
    {
        return ShopProduct::query()->updateOrCreate(
            ['slug' => $slug],
            array_merge([
                'is_published' => true,
                'featured_image' => 'https://picsum.photos/seed/'.$slug.'/960/540',
                'meta_title' => $attributes['title'].' | راهبر حساب',
                'meta_description' => $attributes['description'] ?? null,
            ], $attributes)
        );
    }
}
