<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('cms.app.client_key');
        $presented = (string) $request->header('X-App-Key');

        if ($configured === '' || ! hash_equals($configured, $presented)) {
            return response()->json([
                'message' => 'کلاینت اپ مجاز نیست.',
                'code' => 'client_rejected',
            ], 401);
        }

        return $next($request);
    }
}
