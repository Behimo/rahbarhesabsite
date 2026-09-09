<?php

namespace App\Blocks;

use App\Contracts\BlockInterface;

abstract class AbstractBlock implements BlockInterface
{
    public function assets(): array
    {
        return ['css' => [], 'js' => []];
    }

    public function defaultSettings(): array
    {
        $settings = [];

        foreach ($this->schema() as $key => $field) {
            if (array_key_exists('default', $field)) {
                $settings[$key] = $field['default'];
            } elseif (($field['type'] ?? '') === 'repeater') {
                $settings[$key] = [];
            }
        }

        return $settings;
    }

    protected function view(string $view, array $data = []): string
    {
        return view($view, $data)->render();
    }
}
