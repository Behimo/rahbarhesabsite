<?php

namespace App\Http\Middleware;

use App\Support\AccessCatalog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('admin.login');
        }

        if (! AccessCatalog::allows($user, AccessCatalog::ACCESS_ADMIN)) {
            abort(403, 'دسترسی غیرمجاز.');
        }

        return $next($request);
    }
}
