<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('cms.api_token');

        if (! $token || $request->bearerToken() !== $token) {
            abort(401, 'Unauthorized');
        }

        return $next($request);
    }
}
