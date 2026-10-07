<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('cms.api_token');
        $presented = (string) $request->bearerToken();

        if ($configured === '' || strcasecmp($configured, 'change-me') === 0 || ! hash_equals($configured, $presented)) {
            abort(401, 'Unauthorized');
        }

        return $next($request);
    }
}
