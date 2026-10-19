<?php

namespace App\Http\Middleware;

use App\Models\CmsRedirect;
use App\Services\PermalinkService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HandleCmsRedirects
{
    public static function cacheKey(string $lookup): string
    {
        return 'cms.redirect.'.hash('sha1', $lookup);
    }

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
        $pathWithSlash = str_ends_with($path, '/') || $path === '/' ? $path : $path.'/';
        $pathTrimmed = $path === '/' ? '/' : rtrim($path, '/');

        $queryBased = false;
        $redirect = $this->findRedirect($pathTrimmed);

        if (! $redirect && $pathWithSlash !== $pathTrimmed) {
            $redirect = $this->findRedirect($pathWithSlash);
        }

        if (! $redirect) {
            $redirect = $this->findQueryRedirect($request, $pathTrimmed);
            $queryBased = $redirect !== null;
        }

        if ($redirect) {
            $code = (int) $redirect->status_code;
            if ($code === 410) {
                abort(410, 'Gone');
            }

            $target = $this->safeTarget((string) $redirect->to_path);
            if ($target !== null && ! $this->isSelfLoop($request, $target, $queryBased)) {
                if (! in_array($code, [301, 302, 307, 308], true)) {
                    $code = 301;
                }

                return new RedirectResponse($this->absoluteTarget($target), $code);
            }
        }

        return $next($request);
    }

    private function findRedirect(string $path): ?CmsRedirect
    {
        return Cache::remember(self::cacheKey($path), 3600, function () use ($path) {
            return CmsRedirect::query()
                ->where('from_path', $path)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('match_query', false)->orWhereNull('match_query');
                })
                ->where(function ($q) {
                    $q->whereNull('from_query')->orWhere('from_query', '');
                })
                ->first();
        });
    }

    private function findQueryRedirect(Request $request, string $path): ?CmsRedirect
    {
        $p = $request->query('p');
        if ($p !== null && $p !== '' && ($path === '/' || $path === '')) {
            $lookup = '/?p='.(int) $p;
            $query = 'p='.(int) $p;

            return Cache::remember(self::cacheKey($lookup), 3600, function () use ($lookup, $query) {
                return CmsRedirect::query()
                    ->where('from_path', $lookup)
                    ->where('is_active', true)
                    ->first()
                    ?? CmsRedirect::query()
                        ->where('from_path', '/')
                        ->where('match_query', true)
                        ->where('from_query', $query)
                        ->where('is_active', true)
                        ->first();
            });
        }

        if ($request->query->count() === 0) {
            return null;
        }

        $queryString = $request->getQueryString() ?: '';
        $lookup = $path.($queryString !== '' ? '?'.$queryString : '');

        return Cache::remember(self::cacheKey('q:'.$lookup), 3600, function () use ($path, $queryString, $lookup, $request) {
            $exact = CmsRedirect::query()
                ->where('from_path', $path)
                ->where('match_query', true)
                ->where('from_query', $queryString)
                ->where('is_active', true)
                ->first();

            if ($exact) {
                return $exact;
            }

            // Legacy Rank Math imports stored path?query inside from_path.
            $legacy = CmsRedirect::query()
                ->where('from_path', $lookup)
                ->where('is_active', true)
                ->first();

            if ($legacy) {
                return $legacy;
            }

            // Tolerate extra tracking params: the request must contain every
            // param in the stored query, but may carry additional ones.
            return CmsRedirect::query()
                ->where('from_path', $path)
                ->where('match_query', true)
                ->where('is_active', true)
                ->whereNotNull('from_query')
                ->get()
                ->first(fn (CmsRedirect $redirect): bool => $this->queryIsSubset(
                    (string) $redirect->from_query,
                    $request->query()
                ));
        });
    }

    /**
     * @param  array<string, mixed>  $requestQuery
     */
    private function queryIsSubset(string $fromQuery, array $requestQuery): bool
    {
        if ($fromQuery === '') {
            return false;
        }

        parse_str($fromQuery, $expected);

        if ($expected === []) {
            return false;
        }

        foreach ($expected as $key => $value) {
            if (! array_key_exists($key, $requestQuery)) {
                return false;
            }

            if (is_array($value)) {
                if ($requestQuery[$key] != $value) {
                    return false;
                }
            } elseif ((string) $requestQuery[$key] !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    private function safeTarget(string $to): ?string
    {
        $to = trim($to);

        if ($to === '') {
            return null;
        }

        // Allow explicit http(s) destinations (Rank Math external redirects).
        if (preg_match('#^https?://#i', $to)) {
            return $to;
        }

        if (! str_starts_with($to, '/') || str_starts_with($to, '//') || str_contains($to, '\\')) {
            return null;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', ltrim($to, '/'))) {
            return null;
        }

        return $to;
    }

    /**
     * Never redirect a request back onto the same path — the middleware matches
     * a source regardless of query string, so a same-path target loops forever.
     */
    private function isSelfLoop(Request $request, string $target, bool $queryBased): bool
    {
        if (preg_match('#^https?://#i', $target)) {
            return false;
        }

        $permalinks = app(PermalinkService::class);
        $parts = parse_url($target);
        $targetPath = $permalinks->normalizePath($parts['path'] ?? '/');
        $requestPath = $permalinks->normalizePath('/'.ltrim($request->path(), '/'));

        if ($targetPath !== $requestPath) {
            return false;
        }

        // Path-based match ignores the query string → any same-path target loops.
        if (! $queryBased) {
            return true;
        }

        // Query-based match only loops when it repeats the same query string.
        $targetQuery = $parts['query'] ?? '';

        return $targetQuery !== '' && $targetQuery === ($request->getQueryString() ?: '');
    }

    private function absoluteTarget(string $target): string
    {
        if (preg_match('#^https?://#i', $target)) {
            return $target;
        }

        $permalinks = app(PermalinkService::class);
        $parts = parse_url($target);
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return $permalinks->absolute($permalinks->normalizePath($path)).$query;
    }
}
