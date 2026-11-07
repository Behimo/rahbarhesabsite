<?php

namespace App\Services\Wordpress;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WordpressMedia
{
    public function normalizeMediaUrl(?string $url, int $maxLength = 191): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            $url = map_upload_path($url);

            return strlen($url) > $maxLength ? null : $url;
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return strlen($url) > $maxLength ? null : $url;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        $uploadsPos = strpos($path, '/wp-content/');
        if ($uploadsPos !== false) {
            $path = substr($path, $uploadsPos);
        } else {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $canonical = strtolower((string) config('seo.canonical_host', 'rahbarhesab.com'));
            $host = preg_replace('/^www\./', '', $host) ?? $host;
            $canonical = preg_replace('/^www\./', '', $canonical) ?? $canonical;
            if ($host !== $canonical) {
                return strlen($url) > $maxLength ? null : $url;
            }
        }

        $path = map_upload_path($path);

        return strlen($path) > $maxLength ? null : $path;
    }

    public function relativizeEmbeddedMediaUrls(string $html): string
    {
        $rewritten = preg_replace(
            '#https?://[^"\'\s<>]+(/wp-content/uploads/[^"\'\s<>]*)#iu',
            '$1',
            $html
        );

        return map_upload_path(is_string($rewritten) ? $rewritten : $html);
    }

    public function replaceStoredUploadPrefix(string $table, array $columns): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $updated = 0;

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->where($column, 'like', '%/wp-content/uploads%')
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($table, $column, &$updated): void {
                    foreach ($rows as $row) {
                        $current = $row->{$column} ?? null;
                        if (! is_string($current) || $current === '') {
                            continue;
                        }

                        $rewritten = map_upload_path($current);
                        if ($rewritten === $current) {
                            continue;
                        }

                        DB::table($table)->where('id', $row->id)->update([$column => $rewritten]);
                        $updated++;
                    }
                });
        }

        return $updated;
    }
}
