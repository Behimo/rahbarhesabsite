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

        $html = $this->hydrateLazyMedia($html);
        $html = $this->toSiteRelativeUrls($html);

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

    /**
     * Perfmatters (and similar) leave real image URLs in data-src/data-bg while
     * src is an empty SVG placeholder. Without their JS those never hydrate.
     */
    private function hydrateLazyMedia(string $html): string
    {
        $html = preg_replace_callback(
            '#<img\b[^>]*>#iu',
            function (array $matches): string {
                return $this->hydrateLazyImageTag($matches[0]);
            },
            $html
        ) ?? $html;

        $html = preg_replace_callback(
            '#<video\b[^>]*>#iu',
            function (array $matches): string {
                return $this->hydrateLazyVideoTag($matches[0]);
            },
            $html
        ) ?? $html;

        $html = preg_replace_callback(
            '#<([a-z][a-z0-9]*)\b([^>]*\bdata-bg\s*=\s*(["\'])(.*?)\3[^>]*)>#iu',
            function (array $matches): string {
                return $this->hydrateLazyBackgroundTag($matches[1], $matches[2], $matches[4]);
            },
            $html
        ) ?? $html;

        return $html;
    }

    private function hydrateLazyImageTag(string $tag): string
    {
        $dataSrc = $this->attributeValue($tag, 'data-src')
            ?: $this->attributeValue($tag, 'data-lazy-src')
            ?: $this->attributeValue($tag, 'data-original');

        if ($dataSrc !== null && $dataSrc !== '' && ! str_starts_with($dataSrc, 'data:')) {
            $currentSrc = $this->attributeValue($tag, 'src');
            if ($currentSrc === null || $currentSrc === '' || str_starts_with($currentSrc, 'data:')) {
                $tag = $this->setAttribute($tag, 'src', $dataSrc);
            }
        }

        $dataSrcset = $this->attributeValue($tag, 'data-srcset')
            ?: $this->attributeValue($tag, 'data-lazy-srcset');
        if ($dataSrcset !== null && $dataSrcset !== '') {
            $currentSrcset = $this->attributeValue($tag, 'srcset');
            if ($currentSrcset === null || $currentSrcset === '') {
                $tag = $this->setAttribute($tag, 'srcset', $dataSrcset);
            }
        }

        $dataSizes = $this->attributeValue($tag, 'data-sizes');
        if ($dataSizes !== null && $dataSizes !== '') {
            $currentSizes = $this->attributeValue($tag, 'sizes');
            if ($currentSizes === null || $currentSizes === '') {
                $tag = $this->setAttribute($tag, 'sizes', $dataSizes);
            }
        }

        foreach (['data-src', 'data-lazy-src', 'data-original', 'data-srcset', 'data-lazy-srcset', 'data-sizes'] as $attr) {
            $tag = $this->removeAttribute($tag, $attr);
        }

        return $this->removeLazyClass($tag);
    }

    private function hydrateLazyVideoTag(string $tag): string
    {
        $dataSrc = $this->attributeValue($tag, 'data-src');
        if ($dataSrc !== null && $dataSrc !== '' && ! str_starts_with($dataSrc, 'data:')) {
            $currentSrc = $this->attributeValue($tag, 'src');
            if ($currentSrc === null || $currentSrc === '') {
                $tag = $this->setAttribute($tag, 'src', $dataSrc);
            }
        }

        $dataPoster = $this->attributeValue($tag, 'data-poster');
        if ($dataPoster !== null && $dataPoster !== '' && ! str_starts_with($dataPoster, 'data:')) {
            $currentPoster = $this->attributeValue($tag, 'poster');
            if ($currentPoster === null || $currentPoster === '' || str_starts_with($currentPoster, 'data:')) {
                $tag = $this->setAttribute($tag, 'poster', $dataPoster);
            }
        }

        foreach (['data-src', 'data-poster'] as $attr) {
            $tag = $this->removeAttribute($tag, $attr);
        }

        return $this->removeLazyClass($tag);
    }

    private function hydrateLazyBackgroundTag(string $name, string $attrs, string $bgUrl): string
    {
        $bgUrl = trim(html_entity_decode($bgUrl, ENT_QUOTES | ENT_HTML5));
        if ($bgUrl === '' || str_starts_with($bgUrl, 'data:')) {
            return '<'.$name.$attrs.'>';
        }

        $tag = '<'.$name.$attrs.'>';
        // Bare "style" without a value (common in Elementor+Perfmatters markup).
        $tag = preg_replace('/\s+\bstyle(?=\s|>|\/)/iu', '', $tag) ?? $tag;

        $escapedUrl = str_replace(['(', ')', ' ', '"', "'"], ['%28', '%29', '%20', '%22', '%27'], $bgUrl);
        $bgStyle = 'background-image:url('.$escapedUrl.')';

        if (preg_match('/\sstyle\s*=\s*(["\'])(.*?)\1/iu', $tag, $style)) {
            $existing = trim($style[2]);
            if ($existing === '' || ! preg_match('/background(?:-image)?\s*:/i', $existing)) {
                $merged = $existing === '' ? $bgStyle : rtrim($existing, ';').';'.$bgStyle;
                $tag = preg_replace(
                    '/\sstyle\s*=\s*(["\'])(.*?)\1/iu',
                    ' style="'.htmlspecialchars($merged, ENT_QUOTES | ENT_HTML5).'"',
                    $tag,
                    1
                ) ?? $tag;
            }
        } else {
            $tag = $this->setAttribute($tag, 'style', $bgStyle);
        }

        $tag = $this->removeAttribute($tag, 'data-bg');

        return $this->removeLazyClass($tag);
    }

    /**
     * Collapse absolute URLs for this site (and local APP_URL hosts) to path-only
     * so images keep working regardless of how the site is browsed.
     */
    private function toSiteRelativeUrls(string $html): string
    {
        $hosts = [];
        foreach ([
            (string) config('seo.canonical_host', 'rahbarhesab.com'),
            (string) parse_url((string) config('app.url'), PHP_URL_HOST),
            'localhost',
            '127.0.0.1',
        ] as $host) {
            $host = strtolower(trim($host));
            $host = preg_replace('/^www\./', '', $host) ?? $host;
            if ($host !== '') {
                $hosts[$host] = true;
            }
        }

        foreach (array_keys($hosts) as $host) {
            $quoted = preg_quote($host, '#');
            $html = preg_replace(
                '#https?://(?:www\.)?'.$quoted.'(?::\d+)?(/[^"\'\s<>]*)#iu',
                '$1',
                $html
            ) ?? $html;
        }

        // Uploaded media always lives under /wp-content/ locally after migration.
        $html = preg_replace(
            '#https?://[^"\'\s<>]+(/wp-content/uploads/[^"\'\s<>]*)#iu',
            '$1',
            $html
        ) ?? $html;

        return $html;
    }

    private function attributeNamePattern(string $name): string
    {
        return '(?<![-\w])'.preg_quote($name, '/').'(?![-\w])';
    }

    private function attributeValue(string $tag, string $name): ?string
    {
        $pattern = '/'.$this->attributeNamePattern($name).'\s*=\s*(["\'])(.*?)\1/iu';
        if (! preg_match($pattern, $tag, $matches)) {
            return null;
        }

        return html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5);
    }

    private function setAttribute(string $tag, string $name, string $value): string
    {
        $encoded = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);
        $pattern = '/'.$this->attributeNamePattern($name).'\s*=\s*(["\']).*?\1/iu';
        if (preg_match($pattern, $tag)) {
            return preg_replace($pattern, $name.'="'.$encoded.'"', $tag, 1) ?? $tag;
        }

        return preg_replace('/<([a-z][a-z0-9]*)\b/iu', '<$1 '.$name.'="'.$encoded.'"', $tag, 1) ?? $tag;
    }

    private function removeAttribute(string $tag, string $name): string
    {
        return preg_replace(
            '/\s+'.$this->attributeNamePattern($name).'\s*=\s*(["\']).*?\1/iu',
            '',
            $tag
        ) ?? $tag;
    }

    private function removeLazyClass(string $tag): string
    {
        if (! preg_match('/\sclass\s*=\s*(["\'])(.*?)\1/iu', $tag, $matches)) {
            return $tag;
        }

        $classes = preg_split('/\s+/', trim($matches[2])) ?: [];
        $classes = array_values(array_filter(
            $classes,
            fn (string $class): bool => ! in_array(strtolower($class), [
                'perfmatters-lazy',
                'lazyload',
                'lazy',
                'lazyloaded',
            ], true)
        ));

        if ($classes === []) {
            return $this->removeAttribute($tag, 'class');
        }

        return preg_replace(
            '/\sclass\s*=\s*(["\'])(.*?)\1/iu',
            ' class="'.htmlspecialchars(implode(' ', $classes), ENT_QUOTES | ENT_HTML5).'"',
            $tag,
            1
        ) ?? $tag;
    }
}
