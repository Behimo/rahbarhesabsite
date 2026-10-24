<?php

namespace App\Services;

use App\Support\CssScope;
use Illuminate\Support\Facades\Http;

class PageCssIsolator
{
    public const SCOPE = '.wp-imported-page';

    private const VERSION = '1';

    public function __construct(private CssScope $cssScope) {}

    /**
     * Pull styles out of page HTML and prefix them so they cannot restyle the site chrome.
     *
     * @param  list<string>  $stylesheets
     * @return array{html: string, stylesheet: ?string, css: string}
     */
    public function isolate(string $html, array $stylesheets = []): array
    {
        [$html, $inlineCss, $embeddedSheets] = $this->extractEmbeddedCss($html);
        $stylesheets = array_values(array_filter(array_unique([
            ...$stylesheets,
            ...$embeddedSheets,
        ], SORT_REGULAR)));

        $inline = trim($inlineCss) === ''
            ? ''
            : $this->escapeStyleClose($this->cssScope->scope($inlineCss, self::SCOPE));

        return [
            'html' => $html,
            'stylesheet' => $this->publishStylesheets($stylesheets),
            'css' => $inline,
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: list<string>}
     */
    private function extractEmbeddedCss(string $html): array
    {
        $inline = [];
        $sheets = [];

        $html = preg_replace_callback(
            '#<style\b([^>]*)>(.*?)</style>#is',
            function (array $matches) use (&$inline): string {
                $css = $matches[2];
                if (preg_match('/\bmedia\s*=\s*([\'"])(.*?)\1/i', $matches[1], $media) && trim($media[2]) !== '') {
                    $css = '@media '.$media[2].'{'.$css.'}';
                }
                $inline[] = $css;

                return '';
            },
            $html
        ) ?? $html;

        $html = preg_replace_callback(
            '#<link\b[^>]*>#i',
            function (array $matches) use (&$sheets): string {
                $tag = $matches[0];
                if (! preg_match('/\brel\s*=\s*([\'"])stylesheet\1/i', $tag) && ! preg_match('/\brel\s*=\s*stylesheet(?:\s|>|\/)/i', $tag)) {
                    return $tag;
                }
                if (! preg_match('/\bhref\s*=\s*([\'"])(.*?)\1/i', $tag, $href)) {
                    return '';
                }
                $sheets[] = html_entity_decode($href[2], ENT_QUOTES | ENT_HTML5);

                return '';
            },
            $html
        ) ?? $html;

        return [$html, implode("\n", $inline), $sheets];
    }

    /**
     * @param  list<string>  $stylesheets
     */
    private function publishStylesheets(array $stylesheets): ?string
    {
        if ($stylesheets === []) {
            return null;
        }

        $stamp = self::VERSION.'|'.self::SCOPE;
        $sources = [];
        foreach ($stylesheets as $url) {
            $url = trim($url);
            if ($url === '') {
                continue;
            }
            $local = $this->localPath($url);
            $stamp .= '|'.$url.'|'.($local !== null ? filemtime($local).':'.filesize($local) : 'remote');
            $sources[] = [$url, $local];
        }

        if ($sources === []) {
            return null;
        }

        $relative = 'site/page-scope/'.substr(sha1($stamp), 0, 20).'.css';
        $full = public_path($relative);
        if (! is_file($full)) {
            $css = $this->combine($sources);
            if (trim($css) === '') {
                return null;
            }
            $directory = dirname($full);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            $temporary = $full.'.tmp';
            file_put_contents($temporary, $css, LOCK_EX);
            if (! is_file($full)) {
                rename($temporary, $full);
            }
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }

        return asset($relative);
    }

    /**
     * @param  list<array{0: string, 1: ?string}>  $sources
     */
    private function combine(array $sources): string
    {
        $parts = [];
        foreach ($sources as [$url, $local]) {
            $css = $local !== null ? file_get_contents($local) : $this->fetchRemote($url);
            if (! is_string($css) || trim($css) === '') {
                continue;
            }
            if (str_contains(ltrim($css), '<!') || str_contains(strtolower(ltrim($css)), '<html')) {
                continue;
            }
            $scoped = $this->cssScope->scope($css, self::SCOPE);
            if (trim($scoped) !== '') {
                $parts[] = $scoped;
            }
        }

        return implode("\n", $parts);
    }

    private function fetchRemote(string $url): ?string
    {
        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }
        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'RahbarHesabPageCss/1.0'])
                ->get($url);
        } catch (\Throwable) {
            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    private function localPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || ! str_ends_with(strtolower($path), '.css')) {
            return null;
        }

        $path = rawurldecode(str_replace('\\', '/', $path));
        if (str_contains($path, '..')) {
            return null;
        }

        $full = public_path(ltrim($path, '/'));
        if (! is_file($full)) {
            return null;
        }

        $real = realpath($full);
        $public = realpath(public_path());
        if ($real === false || $public === false || ! str_starts_with($real, $public.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $real;
    }

    private function escapeStyleClose(string $css): string
    {
        return str_ireplace('</style', '<\\/style', $css);
    }
}
