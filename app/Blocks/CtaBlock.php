<?php

namespace App\Blocks;

class CtaBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'cta';
    }

    public function label(): string
    {
        return 'دکمه فراخوان';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'عنوان', 'default' => ''],
            'text' => ['type' => 'text', 'label' => 'متن دکمه', 'default' => 'بیشتر بدانید'],
            'url' => ['type' => 'text', 'label' => 'لینک', 'default' => '#'],
        ];
    }

    public function render(array $settings): string
    {
        return $this->view('blocks.cta', [
            'title' => $settings['title'] ?? '',
            'text' => $settings['text'] ?? 'بیشتر بدانید',
            'url' => $settings['url'] ?? '#',
        ]);
    }
}
