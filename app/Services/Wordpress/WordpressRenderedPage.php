<?php

namespace App\Services\Wordpress;

use Illuminate\Support\Facades\Http;

class WordpressRenderedPage
{
    public function fetchRenderedWpPage(string $slug, int $wpId, string $base, callable $extractRoot, callable $collectStylesheets): ?array
    {
        $candidates = array_values(array_unique(array_filter([
            $base.'/'.rawurlencode($slug).'/',
            $base.'/'.$slug.'/',
            $base.'/?page_id='.$wpId,
            $base.'/?p='.$wpId,
        ])));

        foreach ($candidates as $url) {
            try {
                $response = Http::timeout(25)
                    ->withHeaders(['User-Agent' => 'RahbarHesabWpMigrate/1.0'])
                    ->get($url);
            } catch (\Throwable) {
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $html = (string) $response->body();
            $root = $extractRoot($html);
            if ($root === null || trim($root) === '') {
                continue;
            }

            return [
                'html' => $root,
                'stylesheets' => $collectStylesheets($html, $wpId, $base),
            ];
        }

        return null;
    }

    public function extractElementorRoot(string $html): ?string
    {
        if (! preg_match('/<div[^>]*data-elementor-type=["\']wp-page["\'][^>]*>/i', $html, $match, PREG_OFFSET_CAPTURE)) {
            // Classic / Gutenberg pages: try main content region.
            if (preg_match('/<main\\b[^>]*>([\\s\\S]*?)<\\/main>/i', $html, $main)) {
                return trim($main[1]);
            }
            if (preg_match('/<article\\b[^>]*>([\\s\\S]*?)<\\/article>/i', $html, $article)) {
                return trim($article[1]);
            }

            return null;
        }

        $start = (int) $match[0][1];
        $len = strlen($html);
        $depth = 0;
        $i = $start;
        $end = null;

        while ($i < $len) {
            $nextOpen = stripos($html, '<div', $i);
            $nextClose = stripos($html, '</div>', $i);
            if ($nextClose === false) {
                break;
            }
            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $i = $nextOpen + 4;

                continue;
            }
            $depth--;
            $i = $nextClose + 6;
            if ($depth === 0) {
                $end = $i;
                break;
            }
        }

        if ($end === null) {
            return null;
        }

        return substr($html, $start, $end - $start);
    }

    public function collectElementorStylesheets(string $html, int $wpId, string $siteUrl): array
    {
        preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]*>/i', $html, $linkTags);
        $urls = [];

        foreach ($linkTags[0] as $tag) {
            if (! preg_match('/href=["\']([^"\']+)["\']/i', $tag, $href)) {
                continue;
            }
            $url = html_entity_decode($href[1], ENT_QUOTES | ENT_HTML5);
            $hay = mb_strtolower($url);
            $keep = str_contains($hay, 'elementor')
                || str_contains($hay, 'hello-elementor')
                || str_contains($hay, '/uploads/elementor/css/')
                || str_contains($hay, 'font-awesome')
                || str_contains($hay, 'fontawesome')
                || preg_match('#/post-'.$wpId.'(?:-|$|\\.)#', $hay);

            if (! $keep) {
                continue;
            }

            if (str_starts_with($url, '//')) {
                $url = 'https:'.$url;
            } elseif (str_starts_with($url, '/')) {
                $url = rtrim($siteUrl, '/').$url;
            }

            $urls[] = $url;
        }

        // Always try the generated page CSS even if the <link> tag was missed.
        $urls[] = rtrim($siteUrl, '/').'/wp-content/uploads/elementor/css/post-'.$wpId.'.css';

        return array_values(array_unique($urls));
    }

    public function localizeStylesheets(array $urls, int $wpId, callable $rewriteCss): array
    {
        $localized = [];
        $dir = public_path('migrated-wp/css/'.$wpId);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ($urls as $index => $url) {
            try {
                $response = Http::timeout(20)
                    ->withHeaders(['User-Agent' => 'RahbarHesabWpMigrate/1.0'])
                    ->get($url);
            } catch (\Throwable) {
                $localized[] = $url;

                continue;
            }

            if (! $response->successful()) {
                $localized[] = $url;

                continue;
            }

            $css = (string) $response->body();
            // Skip HTML error pages mistaken for CSS.
            if (str_contains(ltrim($css), '<!doctype') || str_contains(ltrim($css), '<html')) {
                continue;
            }

            $css = $rewriteCss($css, $url);
            $name = sprintf('%02d-%s.css', $index, substr(sha1($url), 0, 10));
            file_put_contents($dir.DIRECTORY_SEPARATOR.$name, $css);
            $localized[] = asset('migrated-wp/css/'.$wpId.'/'.$name);
        }

        return array_values(array_unique($localized));
    }

    public function rewriteCssAssetUrls(string $css, string $cssUrl): string
    {
        $base = preg_replace('#/[^/]*$#', '/', $cssUrl) ?: $cssUrl;
        $pattern = '#url\(([\'"]?)([^)\'"]+)\1\)#i';

        return preg_replace_callback(
            $pattern,
            function (array $m) use ($base) {
                $target = trim($m[2]);
                if ($target === '' || str_starts_with($target, 'data:') || str_starts_with($target, 'http://') || str_starts_with($target, 'https://')) {
                    return $m[0];
                }
                if (str_starts_with($target, '//')) {
                    return 'url('.$m[1].'https:'.$target.$m[1].')';
                }
                if (str_starts_with($target, '/')) {
                    $origin = parse_url($base, PHP_URL_SCHEME).'://'.parse_url($base, PHP_URL_HOST);

                    return 'url('.$m[1].$origin.$target.$m[1].')';
                }

                return 'url('.$m[1].$base.$target.$m[1].')';
            },
            $css
        ) ?? $css;
    }
}
