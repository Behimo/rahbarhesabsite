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
        if ($request->is('admin/*', 'panel/*', 'api/*')) {
            return $next($request);
        }

        if (! Schema::hasTable('cms_redirects')) {
            return $next($request);
        }

        $path = '/'.ltrim($request->path(), '/');

        $redirect = Cache::remember("cms.redirect.{$path}", 3600, function () use ($path) {
            return CmsRedirect::query()
                ->where('from_path', $path)
                ->where('is_active', true)
                ->first();
        });

        if ($redirect) {
            return redirect($redirect->to_path, $redirect->status_code);
        }

        return $next($request);
    }
}
