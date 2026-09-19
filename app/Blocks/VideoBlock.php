<?php

namespace App\Blocks;

class VideoBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'video';
    }

    public function label(): string
    {
        return 'ویدیو';
    }

    public function schema(): array
    {
        return [
            'provider' => ['type' => 'select', 'label' => 'سرویس', 'options' => ['aparat' => 'آپارات', 'youtube' => 'یوتیوب', 'vimeo' => 'ویمئو', 'upload' => 'آپلود مستقیم'], 'default' => 'aparat'],
            'url' => ['type' => 'text', 'label' => 'آدرس ویدیو', 'default' => ''],
        ];
    }

    public function render(array $settings): string
    {
        return $this->blockView('video', [
            'provider' => $settings['provider'] ?? 'aparat',
            'url' => $settings['url'] ?? '',
        ]);
    }
}
