<?php

namespace App\Blocks;

class TextBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'text';
    }

    public function label(): string
    {
        return 'متن';
    }

    public function schema(): array
    {
        return [
            'content' => ['type' => 'richtext', 'label' => 'محتوا', 'default' => ''],
        ];
    }

    public function render(array $settings): string
    {
        return $this->blockView('text', [
            'content' => $settings['content'] ?? '',
        ]);
    }
}
