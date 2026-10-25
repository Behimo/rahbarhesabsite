<?php

namespace App\Routing;

use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator as BaseUrlGenerator;

/**
 * Laravel's UrlGenerator trims trailing slashes from every generated URL.
 * WordPress permalinks keep them — emit the final public URL so in-site
 * navigation never depends on NormalizeSeoUrl's 301 hop.
 */
class UrlGenerator extends BaseUrlGenerator
{
    public function format($root, $path, $route = null)
    {
        $path = '/'.trim($path, '/');

        if ($this->formatHostUsing) {
            $root = call_user_func($this->formatHostUsing, $root, $route);
        }

        if ($this->formatPathUsing) {
            $path = call_user_func($this->formatPathUsing, $path, $route);
        }

        $url = rtrim($root, '/').($path === '/' ? '' : $path);

        if ($path !== '/' && $this->shouldAppendTrailingSlash($path, $route)) {
            $url .= '/';
        }

        return $url === '' ? '/' : $url;
    }

    private function shouldAppendTrailingSlash(string $path, ?Route $route): bool
    {
        if (! config('seo.trailing_slash', true)) {
            return false;
        }

        if (preg_match('/\.[a-zA-Z0-9]{1,8}$/', $path)) {
            return false;
        }

        $routeName = $route?->getName() ?? '';
        if ($routeName !== '' && preg_match('/^(admin|api|livewire|ignition)\b/', $routeName)) {
            return false;
        }

        $first = strtolower(explode('/', ltrim($path, '/'), 2)[0] ?? '');

        return ! in_array($first, [
            'admin',
            'panel',
            'api',
            'livewire',
            'sanctum',
            'up',
            'storage',
            'build',
            'vendor',
            'site',
            'migrated-wp',
        ], true);
    }
}
