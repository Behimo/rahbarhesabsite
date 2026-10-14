<?php

namespace App\Console\Commands;

use App\Models\AccountingLaw;
use App\Models\Category;
use App\Support\PersianSlug;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedAccountingLawsCommand extends Command
{
    protected $signature = 'laws:seed
        {count=10 : تعداد مواد قانونی}
        {--draft : همه را به‌صورت پیش‌نویس بساز (پیش‌فرض: منتشرشده)}
        {--evolutions=3 : تعداد آیتم سیر تحول برای هر ماده}
        {--category= : اسلاگ دسته law (اگر نباشد از دسته‌های نمونه استفاده می‌شود)}';

    protected $description = 'ساخت مواد قانونی فیک برای تست (با سیر تحول و دسته)';

    /** @var list<array{number: string, title: string}> */
    private const ARTICLES = [
        ['number' => '۱۴۷', 'title' => 'هزینه‌های قابل قبول مالیاتی'],
        ['number' => '۱۴۸', 'title' => 'هزینه‌های غیرقابل قبول'],
        ['number' => '۱۶۹', 'title' => 'معاملات فصلی و گزارش‌دهی'],
        ['number' => '۹۵', 'title' => 'دفتر روزنامه و کل'],
        ['number' => '۱۰۳', 'title' => 'حق‌الوکاله و حق‌المشاوره'],
        ['number' => '۱۰۴', 'title' => 'مالیات تکلیفی اجاره'],
        ['number' => '۱۳۱', 'title' => 'نرخ مالیات بر درآمد اشخاص حقیقی'],
        ['number' => '۱۳۲', 'title' => 'معافیت‌های سرمایه‌گذاری'],
        ['number' => '۱۳۸', 'title' => 'معافیت سود سپرده و اوراق'],
        ['number' => '۱۹۰', 'title' => 'جریمه تأخیر در پرداخت مالیات'],
        ['number' => '۱۹۲', 'title' => 'جریمه عدم تسلیم اظهارنامه'],
        ['number' => '۲۷۲', 'title' => 'حسابرسی مالیاتی'],
        ['number' => '۳۸', 'title' => 'حق بیمه سهم کارفرما'],
        ['number' => '۳۹', 'title' => 'حق بیمه سهم بیمه‌شده'],
        ['number' => '۴۸', 'title' => 'بیمه بیکاری'],
    ];

    /** @var list<string> */
    private const CATEGORY_NAMES = ['مالیات', 'بیمه', 'تجارت', 'حسابداری شرکتی'];

    public function handle(): int
    {
        $count = max(1, (int) $this->argument('count'));
        $evolutionCount = max(0, (int) $this->option('evolutions'));
        $asDraft = (bool) $this->option('draft');
        $categorySlug = trim((string) $this->option('category'));

        $categories = $this->resolveCategories($categorySlug);

        if ($categories->isEmpty()) {
            $this->error('هیچ دسته law یافت نشد.');

            return self::FAILURE;
        }

        $created = 0;

        DB::transaction(function () use ($count, $evolutionCount, $asDraft, $categories, &$created): void {
            for ($i = 0; $i < $count; $i++) {
                $sample = self::ARTICLES[$i % count(self::ARTICLES)];
                $suffix = $count > count(self::ARTICLES) ? ' (نمونه '.($i + 1).')' : '';
                $title = $sample['title'].$suffix;
                $slug = PersianSlug::unique(
                    'made-'.$sample['number'].'-'.$title,
                    fn (string $candidate) => AccountingLaw::withTrashed()->where('slug', $candidate)->exists(),
                    'law'
                );

                $law = AccountingLaw::query()->create([
                    'article_number' => $sample['number'],
                    'title' => $title,
                    'slug' => $slug,
                    'introduction' => $this->paragraph(
                        "مقدمه ماده {$sample['number']} درباره «{$sample['title']}». این متن آزمایشی برای بررسی نمایش صفحه است."
                    ),
                    'article_text' => $this->paragraph(
                        "متن رسمی ماده {$sample['number']}: مودیان مکلف‌اند الزامات مربوط به {$sample['title']} را مطابق قانون رعایت کنند. این بند صرفاً برای تست محتواست."
                    ),
                    'use_cases' => $this->paragraph(
                        "موارد استفاده: تنظیم اظهارنامه، رسیدگی هزینه‌ها، پاسخ به ابهام‌های حسابرسی و آموزش تیم مالی درباره {$sample['title']}."
                    ),
                    'plain_language' => $this->paragraph(
                        "به زبان ساده: این ماده مشخص می‌کند در موضوع «{$sample['title']}» چه کارهایی درست و قابل‌قبول است و چه کارهایی مشکل‌ساز می‌شود."
                    ),
                    'example' => $this->paragraph(
                        "مثال: شرکت فرضی آلفا در سال مالی نمونه، اقلام مرتبط با {$sample['title']} را ثبت و مستند کرده و در زمان رسیدگی دچار ابهام نشده است."
                    ),
                    'conclusion' => $this->paragraph(
                        "نتیجه‌گیری: رعایت ماده {$sample['number']} با مستندسازی منظم، ریسک مالیاتی را کاهش می‌دهد."
                    ),
                    'meta_title' => "ماده {$sample['number']} — {$sample['title']}",
                    'meta_description' => "راهنمای آزمایشی ماده {$sample['number']} برای تست بخش مواد قانونی.",
                    'meta_keywords' => 'مواد قانونی, حسابداری, مالیات, تست',
                    'is_published' => ! $asDraft,
                    'status' => $asDraft ? AccountingLaw::STATUS_DRAFT : AccountingLaw::STATUS_PUBLISHED,
                    'published_at' => $asDraft ? null : now()->subDays(($i % 30) + 1),
                    'views' => random_int(0, 500),
                ]);

                $category = $categories[$i % $categories->count()];
                $law->syncCategories([$category->id], $category->id);

                for ($e = 0; $e < $evolutionCount; $e++) {
                    $year = 1395 + $e;
                    $law->evolutions()->create([
                        'event_date' => sprintf('%d/%02d/%02d', $year, (($e * 3) % 12) + 1, (($e * 5) % 27) + 1),
                        'title' => $e === 0 ? 'تصویب اولیه' : ('اصلاحیه '.($e)),
                        'description' => $e === 0
                            ? "ماده {$sample['number']} برای نخستین بار تصویب شد."
                            : "در این تاریخ تغییراتی در مفاد مرتبط با {$sample['title']} اعمال شد.",
                        'sort_order' => $e + 1,
                    ]);
                }

                $created++;
            }
        });

        $state = $asDraft ? 'پیش‌نویس' : 'منتشرشده';
        $this->info("{$created} ماده قانونی {$state} ساخته شد (هر کدام {$evolutionCount} مورد سیر تحول).");
        $this->line('لیست: '.url('/laws'));
        $this->line('ادمین: '.url('/admin/laws'));

        return self::SUCCESS;
    }

    private function paragraph(string $text): string
    {
        return '<p>'.e($text).'</p>';
    }

    /** @return \Illuminate\Support\Collection<int, Category> */
    private function resolveCategories(string $categorySlug)
    {
        if ($categorySlug !== '') {
            $category = Category::query()
                ->ofType(Category::TYPE_LAW)
                ->where(function ($q) use ($categorySlug) {
                    $q->where('slug', $categorySlug)->orWhere('full_slug', $categorySlug);
                })
                ->first();

            return $category ? collect([$category]) : collect();
        }

        $existing = Category::query()->ofType(Category::TYPE_LAW)->orderBy('sort_order')->get();

        if ($existing->isNotEmpty()) {
            return $existing->values();
        }

        $created = collect();
        foreach (self::CATEGORY_NAMES as $index => $name) {
            $slug = PersianSlug::unique(
                $name,
                fn (string $candidate) => Category::withTrashed()
                    ->where('type', Category::TYPE_LAW)
                    ->where('slug', $candidate)
                    ->exists(),
                'law'
            );

            $created->push(Category::query()->create([
                'type' => Category::TYPE_LAW,
                'name' => $name,
                'slug' => $slug,
                'sort_order' => $index,
                'is_active' => true,
            ]));
        }

        $this->comment('دسته‌های نمونه مواد قانونی ساخته شد: '.implode('، ', self::CATEGORY_NAMES));

        return $created->values();
    }
}
