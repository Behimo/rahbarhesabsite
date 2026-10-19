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

            $from = $this->normalizeFrom((string) ($parts[0] ?? ''));
            $to = $this->normalizeTo((string) ($parts[1] ?? ''));
            $code = $this->normalizeCode((string) ($parts[2] ?? '301'));

            if ($from === '' || $to === '' || $from === $to) {
                $skipped++;

                continue;
            }

            CmsRedirect::query()->updateOrCreate(
                ['from_path' => $from],
                [
                    'to_path' => $to,
                    'status_code' => $code,
                    'is_active' => true,
                    'source' => 'rankmath',
                    'match_query' => str_contains($from, '?'),
                ]
            );

            Cache::forget(HandleCmsRedirects::cacheKey($from));
            $map[$from] = $to;
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

    private function normalizeFrom(string $from): string
    {
        $from = trim($from);
        if ($from === '') {
            return '';
        }

        if (str_starts_with($from, 'http://') || str_starts_with($from, 'https://')) {
            $parts = parse_url($from);
            $path = $parts['path'] ?? '/';
            $query = $parts['query'] ?? '';

            return $query !== '' ? $path.'?'.$query : $path;
        }

        if (! str_starts_with($from, '/')) {
            $from = '/'.$from;
        }

        return $from;
    }

    private function normalizeTo(string $to): string
    {
        $to = trim($to);
        if ($to === '') {
            return '';
        }

        if (str_starts_with($to, 'http://') || str_starts_with($to, 'https://')) {
            $parts = parse_url($to);
            $path = $parts['path'] ?? '/';
            $query = isset($parts['query']) ? '?'.$parts['query'] : '';

            return $path.$query;
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
}
