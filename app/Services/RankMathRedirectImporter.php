<?php

namespace App\Services;

use App\Http\Middleware\HandleCmsRedirects;
use App\Models\CmsRedirect;
use Illuminate\Support\Facades\Cache;

class RankMathRedirectImporter
{
    /**
     * Import Rank Math redirect export (CSV or "from,to,type" / "from to code" lines).
     *
     * @return array{imported: int, skipped: int, chains: list<string>}
     */
    public function import(string $payload): array
    {
        $imported = 0;
        $skipped = 0;
        $map = [];

        foreach (preg_split('/\r\n|\r|\n/', $payload) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with(mb_strtolower($line), 'source')) {
                continue;
            }

            // CSV: from,to,type  OR whitespace-separated
            if (str_contains($line, ',')) {
                $parts = str_getcsv($line);
            } else {
                $parts = preg_split('/\s+/', $line) ?: [];
            }

            $fromParts = $this->normalizeFrom((string) ($parts[0] ?? ''));
            $to = $this->normalizeTo((string) ($parts[1] ?? ''));
            $code = $this->normalizeCode((string) ($parts[2] ?? '301'));

            $fromPath = $fromParts['path'];
            $fromQuery = $fromParts['query'];
            $matchQuery = $fromQuery !== '';

            if ($fromPath === '' || $to === '') {
                $skipped++;

                continue;
            }

            $fromKey = $matchQuery ? $fromPath.'?'.$fromQuery : $fromPath;
            if ($this->isSelfRedirect($fromKey, $to)) {
                $skipped++;

                continue;
            }

            // Staging once used /courses/{slug}; WP canonical is /product/{slug}/.
            // Never import the inverted hop that would bounce product pages away.
            if ($this->isInvertedProductCourseRedirect($fromPath, $to)) {
                $skipped++;

                continue;
            }

            // Keep legacy WordPress ?p= shortcuts as a single from_path string.
            if ($fromPath === '/' && str_starts_with($fromQuery, 'p=') && ! str_contains($fromQuery, '&')) {
                $legacyFrom = '/?'.$fromQuery;
                CmsRedirect::query()->updateOrCreate(
                    ['from_path' => $legacyFrom, 'from_query' => ''],
                    [
                        'to_path' => $to,
                        'status_code' => $code,
                        'is_active' => true,
                        'source' => 'rankmath',
                        'match_query' => false,
                        'from_query' => '',
                    ]
                );
                $this->forgetLookup($legacyFrom, '');
                $map[$legacyFrom] = $to;
                $imported++;

                continue;
            }

            CmsRedirect::query()->updateOrCreate(
                ['from_path' => $fromPath, 'from_query' => $fromQuery],
                [
                    'to_path' => $to,
                    'status_code' => $code,
                    'is_active' => true,
                    'source' => 'rankmath',
                    'match_query' => $matchQuery,
                    'from_query' => $fromQuery,
                ]
            );

            $this->forgetLookup($fromPath, $fromQuery);
            $map[$fromKey] = $to;
            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'chains' => $this->detectChains($map),
        ];
    }

    /**
     * @param  array<string, string>  $map
     * @return list<string>
     */
    public function detectChains(array $map): array
    {
        $chains = [];

        foreach ($map as $from => $to) {
            $hop = $to;
            $seen = [$from => true];
            $depth = 0;

            while (isset($map[$hop]) && $depth < 5) {
                if (isset($seen[$hop])) {
                    $chains[] = "loop: {$from} → … → {$hop}";
                    break;
                }
                $seen[$hop] = true;
                $next = $map[$hop];
                if ($depth === 0) {
                    $chains[] = "chain: {$from} → {$hop} → {$next}";
                }
                $hop = $next;
                $depth++;
            }
        }

        return array_values(array_unique($chains));
    }

    /**
     * @return array{path: string, query: string}
     */
    private function normalizeFrom(string $from): array
    {
        $from = trim($from);
        if ($from === '') {
            return ['path' => '', 'query' => ''];
        }

        if (str_starts_with($from, 'http://') || str_starts_with($from, 'https://')) {
            $parts = parse_url($from);
            $path = $parts['path'] ?? '/';
            $query = $parts['query'] ?? '';

            return [
                'path' => $path === '' ? '/' : $path,
                'query' => $query,
            ];
        }

        if (! str_starts_with($from, '/')) {
            $from = '/'.$from;
        }

        $parts = parse_url($from);
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';

        // Relative strings like /old?x=1 are handled by parse_url; also support bare ? in path.
        if ($query === '' && str_contains($from, '?')) {
            [$path, $query] = array_pad(explode('?', $from, 2), 2, '');
        }

        return [
            'path' => $path === '' ? '/' : $path,
            'query' => $query,
        ];
    }

    private function normalizeTo(string $to): string
    {
        $to = trim($to);
        if ($to === '') {
            return '';
        }

        // External destinations keep scheme + host so the redirect actually
        // leaves the site (Rank Math allows off-site targets).
        if (preg_match('#^https?://#i', $to)) {
            return $to;
        }

        if (! str_starts_with($to, '/')) {
            $to = '/'.$to;
        }

        return $to;
    }

    private function normalizeCode(string $code): int
    {
        $code = (int) preg_replace('/\D+/', '', $code) ?: 301;

        return in_array($code, [301, 302, 307, 308, 410], true) ? $code : 301;
    }

    /**
     * Detect a redirect that points back at its own source. Trailing-slash
     * differences are normalised so "/x" → "/x/" is treated as a self-loop
     * (the middleware matches both spellings, so it would loop forever).
     */
    private function isSelfRedirect(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        // External destinations never loop back through this middleware.
        if (preg_match('#^https?://#i', $to)) {
            return false;
        }

        [$fromPath, $fromQuery] = array_pad(explode('?', $from, 2), 2, '');
        [$toPath, $toQuery] = array_pad(explode('?', $to, 2), 2, '');

        return rtrim($fromPath, '/') === rtrim($toPath, '/') && $fromQuery === $toQuery;
    }

    private function isInvertedProductCourseRedirect(string $fromPath, string $to): bool
    {
        if (preg_match('#^https?://#i', $to)) {
            return false;
        }

        $from = rawurldecode(rtrim($fromPath, '/') ?: '/');
        $target = rawurldecode(rtrim(parse_url($to, PHP_URL_PATH) ?: $to, '/') ?: '/');

        return str_starts_with($from, '/product/') && str_starts_with($target, '/courses/');
    }

    private function forgetLookup(string $path, string $query): void
    {
        Cache::forget(HandleCmsRedirects::cacheKey($path));
        if ($query !== '') {
            Cache::forget(HandleCmsRedirects::cacheKey('q:'.$path.'?'.$query));
            Cache::forget(HandleCmsRedirects::cacheKey($path.'?'.$query));
        }
    }
}
