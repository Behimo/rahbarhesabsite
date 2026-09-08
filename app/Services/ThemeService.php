<?php

namespace App\Services;

use Illuminate\Support\Facades\View;

class ThemeService
{
    public function active(): string
    {
        return config('cms.active_theme', 'default');
    }

    public function path(?string $theme = null): string
    {
        return base_path('themes/'.($theme ?? $this->active()));
    }

    public function view(string $view, array $data = []): \Illuminate\Contracts\View\View
    {
        $themeView = 'theme::'.$view;

        if (View::exists($themeView)) {
            return view($themeView, $data);
        }

        return view($view, $data);
    }

    public function registerViews(): void
    {
        $themePath = $this->path();

        if (is_dir($themePath.'/views')) {
            View::addNamespace('theme', $themePath.'/views');
        }
    }
}
