<?php

namespace App\Blocks;

use App\Contracts\BlockInterface;
use Illuminate\Support\Facades\View;

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

    /** Page sections render only inside the site theme. There is no second theme fallback. */
    protected function blockView(string $name, array $data = []): string
    {
        $themeView = 'theme::blocks.'.$name;

        if (! View::exists($themeView)) {
            throw new \InvalidArgumentException("Page block [{$name}] has no view in the site theme.");
        }

        return view($themeView, $data)->render();
    }
}
