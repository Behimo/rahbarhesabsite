<?php

namespace App\Blocks;

class FeaturesBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'features';
    }

    public function label(): string
    {
        return 'کارت‌های ویژگی';
    }

    public function schema(): array
    {
        return [
            'items' => [
                'type' => 'repeater',
                'label' => 'کارت‌ها',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'عنوان'],
                    'text' => ['type' => 'textarea', 'label' => 'توضیح'],
                    'variant' => ['type' => 'select', 'label' => 'چیدمان', 'options' => ['down' => 'پایین', 'up' => 'بالا'], 'default' => 'down'],
                    'panel' => ['type' => 'select', 'label' => 'رنگ پنل', 'options' => ['gray' => 'خاکستری', 'white' => 'سفید'], 'default' => 'gray'],
                ],
            ],
        ];
    }

    public function render(array $settings): string
    {
        return $this->blockView('features', [
            'items' => $settings['items'] ?? [],
        ]);
    }
}
