<?php

namespace Database\Factories;

use App\Models\AccountingLaw;
use App\Support\PersianSlug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountingLaw>
 */
class AccountingLawFactory extends Factory
{
    protected $model = AccountingLaw::class;

    /** @var list<array{number: string, title: string}> */
    private const ARTICLES = [
        ['number' => '۱۴۷', 'title' => 'هزینه‌های قابل قبول مالیاتی'],
        ['number' => '۱۶۹', 'title' => 'معاملات فصلی و گزارش‌دهی'],
        ['number' => '۹۵', 'title' => 'دفتر روزنامه و کل'],
        ['number' => '۱۹۰', 'title' => 'جریمه تأخیر در پرداخت مالیات'],
        ['number' => '۲۷۲', 'title' => 'حسابرسی مالیاتی'],
    ];

    public function definition(): array
    {
        $sample = fake()->randomElement(self::ARTICLES);
        $title = $sample['title'].' '.fake()->unique()->numerify('##');

        return [
            'article_number' => $sample['number'],
            'title' => $title,
            'slug' => PersianSlug::unique(
                'made-'.$sample['number'].'-'.$title,
                fn (string $slug) => AccountingLaw::withTrashed()->where('slug', $slug)->exists(),
                'law'
            ),
            'introduction' => '<p>مقدمه آزمایشی برای ماده '.$sample['number'].'.</p>',
            'article_text' => '<p>متن رسمی آزمایشی ماده '.$sample['number'].' درباره '.$sample['title'].'.</p>',
            'use_cases' => '<p>موارد استفاده آزمایشی برای '.$sample['title'].'.</p>',
            'plain_language' => '<p>توضیح ساده آزمایشی درباره '.$sample['title'].'.</p>',
            'example' => '<p>مثال آزمایشی برای ماده '.$sample['number'].'.</p>',
            'conclusion' => '<p>نتیجه‌گیری آزمایشی ماده '.$sample['number'].'.</p>',
            'featured_image' => null,
            'featured_image_alt' => null,
            'is_published' => false,
            'status' => AccountingLaw::STATUS_DRAFT,
            'published_at' => null,
            'views' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'is_published' => true,
            'status' => AccountingLaw::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
    }
}
