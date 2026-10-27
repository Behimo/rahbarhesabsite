<?php

namespace App\Http\Middleware;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsRedirect;
use App\Models\ShopProduct;
use App\Services\PermalinkService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class HandleCmsRedirects
{
    public function __construct(private PermalinkService $permalinks) {}

    public static function cacheKey(string $lookup): string
    {
        return 'cms.redirect.'.hash('sha1', rtrim(rawurldecode($lookup), '/') ?: '/');
    }

    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();
        if (! in_array($request->method(), ['GET', 'HEAD'], true)
            || $this->permalinks->isSystemPath($path)
            || $this->permalinks->isFilePath($path)) {
            return $next($request);
        }

        $query = $request->query();
        $seen = [];
        $status = null;
        $target = null;
        for ($hop = 0; $hop < 10; $hop++) {
            $identity = (rtrim(rawurldecode($path), '/') ?: '/').'?'.http_build_query($query);
            if (isset($seen[$identity])) {
                return $next($request);
            }
            $seen[$identity] = true;
            $row = $this->lookup($path, $query);
            if (! $row) {
                break;
            }
            if ((int) $row['status_code'] === 410) {
                return response('Gone', 410);
            }
            if (! in_array((int) $row['status_code'], [301, 302, 307, 308], true)
                || ! $this->safeTarget($row['to_path'])) {
                return $next($request);
            }

            $status ??= (int) $row['status_code'];
            $target = $row['to_path'];
            $parts = parse_url($target);
            $host = $parts['host'] ?? null;
            $canonicalHost = preg_replace('/^www\./i', '', (string) config('seo.canonical_host'));
            $localHosts = [parse_url($this->permalinks->rootUrl(), PHP_URL_HOST), $request->getHost(), $canonicalHost, 'www.'.$canonicalHost];
            if ($host && ! in_array(mb_strtolower($host), array_map('mb_strtolower', $localHosts), true)) {
                return redirect()->away($target, $status);
            }
            if (str_starts_with($path, '/product/') && str_starts_with($parts['path'] ?? '', '/courses/')) {
                return $next($request);
            }
            $path = $parts['path'] ?? '/';
            parse_str($parts['query'] ?? '', $query);
            if ($this->permalinks->isSystemPath($path)) {
                break;
            }
            if ($hop === 9) {
                return $next($request);
            }
        }

        if ($target !== null) {
            $parts = parse_url($target);
            $destination = $this->permalinks->absolute($parts['path'] ?? '/');
            $destination .= isset($parts['query']) ? '?'.$parts['query'] : '';
            $destination .= isset($parts['fragment']) ? '#'.$parts['fragment'] : '';
            $original = $this->permalinks->absolute($request->getPathInfo());
            $original .= $request->getQueryString() ? '?'.$request->getQueryString() : '';
            if ($destination !== $original) {
                return redirect()->to($destination, $status);
            }
        }

        return $next($request);
    }

    private function lookup(string $path, array $query): ?array
    {
        $path = rawurldecode($path);
        $lookups = [$path];
        if ($path === '/' && isset($query['p']) && is_scalar($query['p'])) {
            array_unshift($lookups, '/?p='.$query['p']);
        }
        foreach ($lookups as $lookup) {
            $trimmed = rtrim($lookup, '/') ?: '/';
            $rows = Cache::remember(self::cacheKey($trimmed), 300, fn () => CmsRedirect::query()
                ->whereIn('from_path', array_unique([$trimmed, $trimmed === '/' ? '/' : $trimmed.'/']))
                ->where('is_active', true)->get()->toArray());
            usort($rows, fn ($a, $b) => strlen((string) ($b['from_query'] ?? '')) <=> strlen((string) ($a['from_query'] ?? '')));
            foreach ($rows as $row) {
                if ($row['match_query']) {
                    parse_str((string) $row['from_query'], $required);
                    if ($required === [] || array_intersect_key($query, $required) != $required) {
                        continue;
                    }
                }

                return $row;
            }
        }

        if ($path === '/' && isset($query['p']) && is_scalar($query['p']) && ctype_digit((string) $query['p'])) {
            foreach ([CmsPost::class, CmsPage::class, ShopProduct::class] as $model) {
                $content = $model::query()->published()->where('wp_post_id', $query['p'])->first();
                if ($content) {
                    $to = match ($model) {
                        CmsPost::class => $this->permalinks->postAbsolute($content),
                        CmsPage::class => $this->permalinks->pageAbsolute($content),
                        default => $this->permalinks->productAbsolute($content),
                    };

                    return ['to_path' => $to, 'status_code' => 301];
                }
            }
        }

        return null;
    }

    private function safeTarget(string $target): bool
    {
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $target)) {
            return false;
        }
        if (str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
            return true;
        }
        $parts = parse_url($target);

        return $parts && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && ! empty($parts['host']) && ! isset($parts['user']) && ! isset($parts['pass']);
    }
}
