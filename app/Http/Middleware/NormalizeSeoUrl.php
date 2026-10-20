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
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        if ($request->is('admin/*', 'panel/*', 'api/*', 'livewire/*', 'up')) {
            return $next($request);
        }

        // PHPUnit pathInfo already strips trailing slashes — avoid redirect loops in tests.
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        $hostRedirect = $this->hostRedirect($request);
        if ($hostRedirect !== null) {
            return $hostRedirect;
        }

        $slashRedirect = $this->trailingSlashRedirect($request);
        if ($slashRedirect !== null) {
            return $slashRedirect;
        }

        return $next($request);
    }

    private function hostRedirect(Request $request): ?Response
    {
        $canonicalHost = strtolower((string) config('seo.canonical_host', 'rahbarhesab.com'));
        $host = strtolower($request->getHost());

        $isCanonical = $host === $canonicalHost;
        $isWwwVariant = $host === 'www.'.$canonicalHost;

        // Only force HTTPS on the configured canonical host. Staging hosts
        // (e.g. delicollection.ir) must not enter a redirect loop when PHP-FPM
        // sees plain HTTP behind a TLS-terminating proxy.
        $needsHttps = config('seo.force_https', true)
            && ! $request->secure()
            && app()->environment('production')
            && ($isCanonical || $isWwwVariant);

        if (! $needsHttps && ! $isWwwVariant) {
            return null;
        }

        $scheme = (config('seo.force_https', true) || $request->secure()) ? 'https' : $request->getScheme();
        $targetHost = $isWwwVariant ? $canonicalHost : $host;
        $uri = $request->getRequestUri();

        return redirect()->to($scheme.'://'.$targetHost.$uri, 301);
    }

    private function trailingSlashRedirect(Request $request): ?Response
    {
        if (! config('seo.trailing_slash', true)) {
            return null;
        }

        // Use raw request URI so we see the browser-facing slash, not pathInfo.
        $uriPath = parse_url($request->getRequestUri(), PHP_URL_PATH) ?: '/';

        if ($uriPath === '/') {
            return null;
        }

        if (preg_match('/\.[a-zA-Z0-9]{1,8}$/', $uriPath)) {
            return null;
        }

        if (str_ends_with($uriPath, '/')) {
            return null;
        }

        $normalized = $this->permalinks->normalizePath($uriPath);
        $query = $request->getQueryString();
        // Use absolute URL + RedirectResponse so Laravel does not strip the trailing slash.
        $target = $this->permalinks->absolute($normalized).($query ? '?'.$query : '');

        return new \Illuminate\Http\RedirectResponse($target, 301);
    }
}
