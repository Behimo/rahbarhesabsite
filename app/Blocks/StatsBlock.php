<?php

namespace App\Blocks;

class StatsBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'stats';
    }

    public function label(): string
    {
        return 'آمار';
    }

    public function schema(): array
    {
        return [
            'items' => ['type' => 'repeater', 'label' => 'آمار', 'default' => [
                ['value' => '۱۰۰+', 'label' => 'مشتری'],
            ], 'fields' => [
                'value' => ['type' => 'text', 'label' => 'مقدار', 'default' => ''],
                'label' => ['type' => 'text', 'label' => 'برچسب', 'default' => ''],
            ]],
        ];
    }

    public function render(array $settings): string
    {
        return $this->blockView('stats', [
            'items' => $settings['items'] ?? [],
        ]);
    }
}
