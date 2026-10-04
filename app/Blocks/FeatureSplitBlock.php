<?php

namespace App\Blocks;

class FeatureSplitBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'feature-split';
    }

    public function label(): string
    {
        return 'سکشن دو ستونه';
    }

    public function schema(): array
    {
        return [
            'layout' => [
                'type' => 'select',
                'label' => 'نوع سکشن',
                'options' => [
                    'app' => 'اپلیکیشن راهبر',
                    'finance' => 'راهبر مالی',
                    'system' => 'راهبر سیستم',
                    'mentor' => 'راهبر مشاور',
                    'instagram' => 'اینستاگرام',
                    'experiences' => 'تجربه کاربران',
                    'partners' => 'همکاران',
                ],
                'default' => 'app',
            ],
            'heading' => ['type' => 'text', 'label' => 'عنوان', 'default' => ''],
            'description' => ['type' => 'textarea', 'label' => 'توضیحات', 'default' => ''],
            'image' => ['type' => 'image', 'label' => 'تصویر', 'default' => ''],
            'cta_text' => ['type' => 'text', 'label' => 'متن دکمه', 'default' => ''],
            'cta_url' => ['type' => 'text', 'label' => 'لینک دکمه', 'default' => '#'],
            'items' => [
                'type' => 'repeater',
                'label' => 'آیتم‌ها',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'عنوان'],
                    'text' => ['type' => 'textarea', 'label' => 'متن'],
                    'image' => ['type' => 'image', 'label' => 'تصویر'],
                    'url' => ['type' => 'text', 'label' => 'لینک'],
                ],
            ],
        ];
    }

    public function render(array $settings): string
    {
        $layout = $settings['layout'] ?? 'app';

        return $this->blockView('feature-split', [
            'layout' => $layout,
            'ctaText' => $settings['cta_text'] ?? '',
            'ctaUrl' => $settings['cta_url'] ?? '',
            'appDownloadUrl' => $settings['cta_url'] ?? '',
        ] + $settings);
    }
}
