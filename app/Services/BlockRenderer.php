<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BlockRenderer
{
    public function __construct(
        private BlockRegistry $registry,
        private PageRenderContext $context,
    ) {}

    /** @param  array<string, mixed>  $contextData */
    public function render(array $builderContent, array $contextData = []): string
    {
        $this->context->set($contextData);

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
