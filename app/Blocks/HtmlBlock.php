<?php

namespace App\Blocks;

class HtmlBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'html';
    }

    public function label(): string
    {
        return 'HTML سفارشی';
    }

    public function schema(): array
    {
        return [
            'html' => ['type' => 'code', 'label' => 'HTML', 'default' => ''],
        ];
    }

    public function render(array $settings): string
    {
        return $this->view('blocks.html', [
            'html' => $settings['html'] ?? '',
        ]);
    }
}
