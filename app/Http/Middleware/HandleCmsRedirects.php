<?php

namespace App\Http\Middleware;

use App\Models\CmsRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HandleCmsRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin/*', 'panel/*', 'api/*', 'login', 'login/*', 'logout', 'up')) {
            return $next($request);
        }

        $tableReady = Cache::remember('schema.cms_redirects', 300, fn () => Schema::hasTable('cms_redirects'));

        if (! $tableReady) {
            return $next($request);
        }

        $path = '/'.ltrim($request->path(), '/');

        $redirect = Cache::remember("cms.redirect.{$path}", 3600, function () use ($path) {
            return CmsRedirect::query()
                ->where('from_path', $path)
                ->where('is_active', true)
                ->first();
        });

        if ($redirect && ($target = $this->safeTarget((string) $redirect->to_path)) !== null) {
            return redirect($target, $redirect->status_code);
        }

        return $next($request);
    }

    private function safeTarget(string $to): ?string
    {
        $to = trim($to);

        if ($to === '' || ! str_starts_with($to, '/') || str_starts_with($to, '//') || str_contains($to, '\\')) {
            return null;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', ltrim($to, '/'))) {
            return null;
        }

        return $to;
    }
}
