<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleGoneWordpressPaths
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->getPathInfo(), '/');
        $gone = config('seo.gone_paths', []);

        $candidates = array_unique([
            $path,
            rtrim($path, '/') ?: '/',
            str_ends_with($path, '/') ? $path : $path.'/',
        ]);

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $gone, true)) {
                abort(410, 'Gone');
            }
        }

        // /wp-json and nested wp-admin paths
        if ($request->is('wp-json', 'wp-json/*', 'wp-admin', 'wp-admin/*')) {
            abort(410, 'Gone');
        }

        return $next($request);
    }
}
