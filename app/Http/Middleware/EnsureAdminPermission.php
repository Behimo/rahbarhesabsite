<?php

namespace App\Http\Middleware;

use App\Support\AccessCatalog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('admin.login');
        }

        $required = $permission ?: AccessCatalog::permissionForRoute($request->route()?->getName());

        if (! AccessCatalog::allows($user, $required)) {
            abort(403, 'دسترسی غیرمجاز.');
        }

        return $next($request);
    }
}
