<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BlockRenderer
{
    public function __construct(private BlockRegistry $registry) {}

    public function render(array $builderContent): string
    {
        $blocks = $builderContent['blocks'] ?? [];
        $html = '';

        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;
            $settings = $block['settings'] ?? [];

            if (! $type) {
                continue;
            }

            try {
                $html .= $this->registry->renderBlock($type, $settings);
            } catch (\Throwable $e) {
                Log::error('Block render failed: ' . $type, ['error' => $e->getMessage()]);
                continue;
            }
        }

        return $html;
    }

    public function assets(array $builderContent): array
    {
        return $this->registry->collectAssets($builderContent['blocks'] ?? []);
    }
}
