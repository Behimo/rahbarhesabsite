<?php

namespace App\Blocks;

class ImageBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'image';
    }

    public function label(): string
    {
        return 'تصویر';
    }

    public function schema(): array
    {
        return [
            'src' => ['type' => 'image', 'label' => 'تصویر', 'default' => ''],
            'alt' => ['type' => 'text', 'label' => 'متن جایگزین', 'default' => ''],
            'caption' => ['type' => 'text', 'label' => 'توضیح', 'default' => ''],
        ];
    }

    public function render(array $settings): string
    {
        return $this->blockView('image', [
            'src' => $settings['src'] ?? '',
            'alt' => $settings['alt'] ?? '',
            'caption' => $settings['caption'] ?? '',
        ]);
    }
}
