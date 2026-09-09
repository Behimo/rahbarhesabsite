<?php

namespace App\Services;

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
            } catch (\Throwable) {
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
