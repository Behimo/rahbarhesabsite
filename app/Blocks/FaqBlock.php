<?php

namespace App\Blocks;

class FaqBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'faq';
    }

    public function label(): string
    {
        return 'سوالات متداول';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'عنوان بخش', 'default' => 'سوالات متداول'],
            'items' => ['type' => 'repeater', 'label' => 'سوالات', 'default' => [
                ['question' => '', 'answer' => ''],
            ], 'fields' => [
                'question' => ['type' => 'text', 'label' => 'سؤال', 'default' => ''],
                'answer' => ['type' => 'textarea', 'label' => 'پاسخ', 'default' => ''],
            ]],
        ];
    }

    public function render(array $settings): string
    {
        return $this->view('blocks.faq', [
            'title' => $settings['title'] ?? 'سوالات متداول',
            'items' => $settings['items'] ?? [],
        ]);
    }
}
