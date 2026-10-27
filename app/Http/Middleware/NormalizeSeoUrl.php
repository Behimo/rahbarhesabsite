<?php

namespace App\Http\Middleware;

use App\Services\PermalinkService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeSeoUrl
{
    public function __construct(private PermalinkService $permalinks) {}

    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();
        if (! in_array($request->method(), ['GET', 'HEAD'], true)
            || ! app()->environment(config('seo.normalization_environments', ['production']))
            || $this->permalinks->isSystemPath($path)
            || $this->permalinks->isFilePath($path)) {
            return $next($request);
        }

        $target = $this->permalinks->absolute($path);
        $current = $request->getSchemeAndHttpHost().$path;
        if ($target !== $current) {
            $query = $request->getQueryString();

            return redirect()->to($target.($query ? '?'.$query : ''), 301);
        }

        return $next($request);
    }
}
