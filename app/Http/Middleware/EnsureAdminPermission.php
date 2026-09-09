<?php

namespace App\Http\Middleware;

use App\Support\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $admin = Auth::guard('cms')->user();

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $required = $permission ?? $this->resolveFromRoute($request);

        if ($required && ! $admin->hasPermission($required)) {
            abort(403, 'دسترسی غیرمجاز.');
        }

        return $next($request);
    }

    private function resolveFromRoute(Request $request): ?string
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return null;
        }

        foreach (Permission::routeMap() as $pattern => $permission) {
            if (str_ends_with($pattern, '.*')) {
                $prefix = rtrim($pattern, '.*');
                if (str_starts_with($routeName, $prefix)) {
                    return $permission;
                }
            } elseif ($routeName === $pattern) {
                return $permission;
            }
        }

        return null;
    }
}
