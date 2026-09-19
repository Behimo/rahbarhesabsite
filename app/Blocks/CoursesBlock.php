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
        return 'کاروسل دوره‌ها';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'عنوان', 'default' => 'جدیدترین دوره های ما'],
            'section_id' => ['type' => 'text', 'label' => 'شناسه HTML', 'default' => 'latestCourses'],
            'section_class' => ['type' => 'text', 'label' => 'کلاس CSS', 'default' => ''],
            'limit' => ['type' => 'number', 'label' => 'تعداد', 'default' => 12],
            'filter' => ['type' => 'select', 'label' => 'فیلتر', 'options' => ['latest' => 'جدیدترین', 'free' => 'رایگان'], 'default' => 'latest'],
        ];
    }

    public function render(array $settings): string
    {
        $filter = $settings['filter'] ?? 'latest';
        $limit = (int) ($settings['limit'] ?? 12);

        $query = ShopProduct::query()
            ->published()
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with(['course.instructor'])
            ->orderBy('sort_order');

        if ($filter === 'free') {
            $query->where('price', 0);
        }

        $courses = $query->limit($limit)->get();

        if ($filter === 'free' && $courses->isEmpty()) {
            return '';
        }

        return $this->blockView('courses', [
            'title' => $settings['title'] ?? 'جدیدترین دوره های ما',
            'sectionId' => $settings['section_id'] ?? 'latestCourses',
            'sectionClass' => $settings['section_class'] ?? '',
            'isFree' => $filter === 'free',
            'courses' => $courses,
        ]);
    }
}
