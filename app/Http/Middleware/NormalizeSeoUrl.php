<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Intentionally does not redirect.
 *
 * Public URLs are generated already in WordPress form (trailing slash, /product/...).
 * Serving the same content on a mistyped host/slash must not 301 — the SEO cutover
 * contract is 200 on the canonical path, not hop-to-canonical.
 */
class NormalizeSeoUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
