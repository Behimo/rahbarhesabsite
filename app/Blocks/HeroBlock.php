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
        return 'بخش Hero / درباره';
    }

    public function schema(): array
    {
        return [
            'eyebrow' => ['type' => 'text', 'label' => 'عنوان کوچک', 'default' => 'راهبر حساب؛ خالق رهبران حسابداری'],
            'title' => ['type' => 'text', 'label' => 'عنوان اصلی', 'default' => 'موسسه آموزشی حسابداری و خدمات مالی و مالیاتی'],
            'subtitle' => ['type' => 'textarea', 'label' => 'توضیحات', 'default' => 'آموزش تخصصی حسابداری برای بازار کار و ارائه خدمات مالی و مالیاتی به حسابداران و کسب و کارها در سراسر ایران؛ برگزارکننده نخستین همایش ۱۰۰۰ نفره حسابداری در کشور'],
            'cta_text' => ['type' => 'text', 'label' => 'متن دکمه', 'default' => ''],
            'cta_url' => ['type' => 'text', 'label' => 'لینک دکمه', 'default' => '#'],
            'background_image' => ['type' => 'image', 'label' => 'تصویر بنر', 'default' => ''],
        ];
    }

    public function render(array $settings): string
    {
        return $this->blockView('hero', [
            'heading_small' => $settings['eyebrow'] ?? '',
            'heading' => $settings['title'] ?? '',
            'description' => $settings['subtitle'] ?? '',
            'banner_image' => $settings['background_image'] ?? '',
            'title' => $settings['title'] ?? '',
            'subtitle' => $settings['subtitle'] ?? '',
            'ctaText' => $settings['cta_text'] ?? '',
            'ctaUrl' => $settings['cta_url'] ?? '#',
            'backgroundImage' => $settings['background_image'] ?? null,
        ]);
    }
}
