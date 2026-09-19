<?php

namespace App\Blocks;

class TestimonialsBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'testimonials';
    }

    public function label(): string
    {
        return 'نظرات مشتریان';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'عنوان', 'default' => 'نظرات مشتریان'],
            'items' => ['type' => 'repeater', 'label' => 'نظرات', 'default' => [
                ['name' => '', 'role' => '', 'quote' => ''],
            ], 'fields' => [
                'name' => ['type' => 'text', 'label' => 'نام', 'default' => ''],
                'role' => ['type' => 'text', 'label' => 'سمت', 'default' => ''],
                'quote' => ['type' => 'textarea', 'label' => 'متن', 'default' => ''],
            ]],
        ];
    }

    public function render(array $settings): string
    {
        return $this->blockView('testimonials', [
            'title' => $settings['title'] ?? '',
            'items' => $settings['items'] ?? [],
        ]);
    }
}
