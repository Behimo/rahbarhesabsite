<?php

namespace App\Routing;

use App\Services\PermalinkService;
use Illuminate\Routing\UrlGenerator as BaseUrlGenerator;

/** Preserve WordPress trailing slashes after Laravel formats the URL. */
class UrlGenerator extends BaseUrlGenerator
{
    public function format($root, $path, $route = null)
    {
        $url = parent::format($root, $path, $route);
        $normalized = app(PermalinkService::class)->normalizePath($path);

        return str_ends_with($normalized, '/') && $normalized !== '/' ? $url.'/' : $url;
    }
}
