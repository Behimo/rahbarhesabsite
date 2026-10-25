<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CMS/Rank Math redirect table is retained for admin tooling only.
 * Public requests are never redirected — URLs must be the final WP paths.
 */
class HandleCmsRedirects
{
    public static function cacheKey(string $lookup): string
    {
        return 'cms.redirect.'.hash('sha1', $lookup);
    }

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
