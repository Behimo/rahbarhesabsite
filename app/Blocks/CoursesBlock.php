<?php

namespace App\Blocks;

use App\Models\ShopProduct;

class CoursesBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'courses';
    }

    public function label(): string
    {
        return 'لیست دوره‌ها';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'عنوان', 'default' => 'دوره‌های آموزشی'],
            'limit' => ['type' => 'number', 'label' => 'تعداد', 'default' => 4],
        ];
    }

    public function render(array $settings): string
    {
        $courses = ShopProduct::query()
            ->published()
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with('course')
            ->orderBy('sort_order')
            ->limit((int) ($settings['limit'] ?? 4))
            ->get();

        return $this->view('blocks.courses', [
            'title' => $settings['title'] ?? 'دوره‌های آموزشی',
            'courses' => $courses,
        ]);
    }
}
