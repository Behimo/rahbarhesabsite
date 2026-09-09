<?php

namespace App\Blocks;

class HeroBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return 'بخش Hero';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'عنوان', 'default' => ''],
            'subtitle' => ['type' => 'textarea', 'label' => 'زیرعنوان', 'default' => ''],
            'cta_text' => ['type' => 'text', 'label' => 'متن دکمه', 'default' => ''],
            'cta_url' => ['type' => 'text', 'label' => 'لینک دکمه', 'default' => '#'],
            'background_image' => ['type' => 'image', 'label' => 'تصویر پس‌زمینه', 'default' => ''],
        ];
    }

    public function render(array $settings): string
    {
        return $this->view('blocks.hero', [
            'title' => $settings['title'] ?? '',
            'subtitle' => $settings['subtitle'] ?? '',
            'ctaText' => $settings['cta_text'] ?? '',
            'ctaUrl' => $settings['cta_url'] ?? '#',
            'backgroundImage' => $settings['background_image'] ?? null,
        ]);
    }
}
