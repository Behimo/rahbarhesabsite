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
        ];
    }

    public function render(array $settings): string
    {
        $layout = $settings['layout'] ?? 'app';

        if ($layout === 'experiences' && ! file_exists(public_path('themes/rahbarhesab/images/experience-1.jpg'))) {
            return '';
        }

        if ($layout === 'partners' && ! file_exists(public_path('themes/rahbarhesab/images/partner-sepidar.png'))) {
            return '';
        }

        return $this->blockView('feature-split', [
            'layout' => $layout,
            'appDownloadUrl' => $settings['cta_url'] ?? '#',
        ] + $settings);
    }
}
