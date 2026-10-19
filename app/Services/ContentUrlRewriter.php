<?php

namespace App\Services;

/**
 * Rewrite legacy WordPress URLs inside HTML content to final parity paths.
 * Prefer direct final URLs over relying on 301 tables for internal links.
 */
class ContentUrlRewriter
{
    public function __construct(private PermalinkService $permalinks) {}

    public function rewrite(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $host = preg_quote((string) config('seo.canonical_host', 'rahbarhesab.com'), '#');
        $base = rtrim((string) config('app.url'), '/');

        // Absolute site URLs → path-only (keeps same path; host normalized).
        $html = preg_replace(
            '#https?://(?:www\.)?'.$host.'(/[^"\'\s<>]*)#iu',
            $base.'$1',
            $html
        ) ?? $html;

        // Staging-era Laravel course URLs → /product/
        $html = preg_replace('#(/courses/)([^"\'\s<>?/]+)/?#u', '/product/$2/', $html) ?? $html;
        $html = preg_replace('#(/p/)([^"\'\s<>?/]+)/?#u', '/$2/', $html) ?? $html;

        // Normalize trailing slash on internal href/src
        $html = preg_replace_callback(
            '#\b(href|src)=["\'](/[^"\']+)["\']#u',
            function (array $m) {
                $attr = $m[1];
                $path = $m[2];
                if (preg_match('/\.[a-zA-Z0-9]{1,8}$/', $path) || str_starts_with($path, '/wp-content/')) {
                    return $attr.'="'.$path.'"';
                }

                return $attr.'="'.$this->permalinks->normalizePath($path).'"';
            },
            $html
        ) ?? $html;

        return $html;
    }
}
