<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Support\CssScope;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigratedWpPageConverter
{
    /**
     * Stable ASCII keys for Persian (or awkward) slugs.
     *
     * @var array<string, string>
     */
    public const SLUG_KEYS = [
        'سیاست-حفظ-حریم-خصوصی' => 'privacy-policy',
        'نسبت-سود-فعالیت-مشاغل' => 'profit-ratios',
        'tax-consulting-services' => 'tax-consulting-services',
        'terms' => 'terms',
        'customer-reviews' => 'customer-reviews',
        'training-course-registration-guide' => 'training-course-registration-guide',
        'careers' => 'careers',
        'software' => 'software',
        'certificates' => 'certificates',
        'cip-members' => 'cip-members',
        'pos-services' => 'pos-services',
        'licenses' => 'licenses',
        'representatives' => 'representatives',
        'easy-invoice-representatives' => 'easy-invoice-representatives',
        'support' => 'support',
        'terms-and-conditions' => 'terms-and-conditions',
        'calculation-of-eid-and-year-end-annuities' => 'calculation-of-eid-and-year-end-annuities',
        'rahbardesk' => 'rahbardesk',
        'lan-software' => 'lan-software',
        'lan-learn' => 'lan-learn',
        'lan-pricingoffancialprojects' => 'lan-pricingoffancialprojects',
        'application' => 'application',
    ];

    public function __construct(
        private CssScope $cssScope,
        private ContentUrlRewriter $urlRewriter,
    ) {}

    public function keyForSlug(string $slug): string
    {
        if (isset(self::SLUG_KEYS[$slug])) {
            return self::SLUG_KEYS[$slug];
        }

        $key = Str::slug($slug);
        if ($key === '') {
            $key = 'page-'.substr(sha1($slug), 0, 10);
        }

        return $key;
    }

    /**
     * @return array{key: string, view: string, css: string, html_bytes: int, css_bytes: int}
     */
    public function convert(CmsPage $page): array
    {
        $slug = (string) $page->slug;
        $key = $this->keyForSlug($slug);
        $content = is_array($page->content) ? $page->content : [];

        $html = $this->urlRewriter->rewrite((string) ($content['body_html'] ?? ''));
        $sheetUrls = array_values(array_filter((array) ($content['stylesheet_urls'] ?? [])));
        $css = $this->buildScopedCss($sheetUrls, $html, $key);
        $html = $this->cleanHtml($html);

        $cssRelative = 'site/pages/'.$key.'.css';
        $cssFull = public_path($cssRelative);
        File::ensureDirectoryExists(dirname($cssFull));
        File::put($cssFull, $css);

        $this->writeHtmlInclude($key, $html);

        $viewRelative = 'pages.migrated.'.$key;
        $bladePath = resource_path('views/pages/migrated/'.$key.'.blade.php');
        File::ensureDirectoryExists(dirname($bladePath));
        File::put($bladePath, $this->bladeTemplate($key));

        return [
            'key' => $key,
            'view' => $viewRelative,
            'css' => $cssRelative,
            'html_bytes' => strlen($html),
            'css_bytes' => strlen($css),
        ];
    }

    /**
     * @param  list<string>  $sheetUrls
     */
    private function buildScopedCss(array $sheetUrls, string $html, string $key): string
    {
        $scope = '.page-migrated.page-'.$key;
        $parts = [];

        foreach ($sheetUrls as $url) {
            $local = $this->localCssPath($url);
            if ($local === null) {
                continue;
            }
            $raw = File::get($local);
            if (! is_string($raw) || trim($raw) === '') {
                continue;
            }
            if (str_contains(ltrim($raw), '<!') || str_contains(strtolower(ltrim($raw)), '<html')) {
                continue;
            }
            $parts[] = $this->stripCssComments($raw);
        }

        // Inline <style> blocks that may still be in the HTML source before cleaning.
        if (preg_match_all('#<style\b[^>]*>(.*?)</style>#is', $html, $matches)) {
            foreach ($matches[1] as $inline) {
                $parts[] = $this->stripCssComments($inline);
            }
        }

        $combined = implode("\n", array_filter($parts, fn ($p) => trim($p) !== ''));
        $scoped = $this->cssScope->scope($combined, $scope);

        $chrome = <<<CSS
{$scope} {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: clip;
}
{$scope} .elementor-location-header,
{$scope} .elementor-location-footer {
    display: none !important;
}
CSS;

        return trim($chrome."\n".$scoped)."\n";
    }

    public function cleanHtml(string $html): string
    {
        // Drop style/link tags — CSS is published separately.
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;
        $html = preg_replace('#<link\b[^>]*rel\s*=\s*([\'"])stylesheet\1[^>]*>#i', '', $html) ?? $html;

        // Elementor / WP HTML comments.
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;

        // Noisy data-* attributes from Elementor / WP.
        $html = preg_replace(
            '/\s+data-(?:elementor[\w-]*|settings|widget_type|widget-id|model-cid|element_type|id)\s*=\s*(["\'])(?:\\\\.|(?!\1).)*\1/i',
            '',
            $html
        ) ?? $html;

        // Empty class/id attributes.
        $html = preg_replace('/\s(?:class|id)=(["\'])\s*\1/i', '', $html) ?? $html;

        // Collapse excessive whitespace between tags without touching text nodes aggressively.
        $html = preg_replace('/>\s{2,}</u', '> <', $html) ?? $html;

        return trim($html);
    }

    private function bladeTemplate(string $key): string
    {
        return <<<BLADE
@extends('layouts.site')

@include('pages.migrated._assets')

@section('page')
    <div class="page-migrated page-{$key} rtl" dir="rtl">
        @include('pages.migrated.html.{$key}')
    </div>
@endsection

BLADE;
    }

    /**
     * Store raw HTML beside a tiny Blade wrapper so Blade does not parse Elementor markup.
     */
    public function writeHtmlInclude(string $key, string $html): void
    {
        $dir = resource_path('views/pages/migrated/html');
        File::ensureDirectoryExists($dir);
        File::put($dir.DIRECTORY_SEPARATOR.$key.'.html', $html);
        File::put(
            $dir.DIRECTORY_SEPARATOR.$key.'.blade.php',
            "<?php echo file_get_contents(resource_path('views/pages/migrated/html/{$key}.html')); ?>\n"
        );
    }

    private function stripCssComments(string $css): string
    {
        return trim(preg_replace('!/\*.*?\*/!s', '', $css) ?? $css);
    }

    private function localCssPath(string $url): ?string
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

    /**
     * Rewrite config/cms_migrated_pages.php from a map of slug => meta.
     *
     * @param  array<string, array{view: string, css: string, key: string}>  $pages
     */
    public function writeConfig(array $pages): void
    {
        ksort($pages);
        $export = var_export(['pages' => $pages], true);
        $contents = <<<PHP
<?php

/**
 * WordPress-imported pages rewritten to Blade + scoped CSS.
 * Keys are CmsPage slugs. Values: view name + public-relative CSS path.
 *
 * Regenerated by `php artisan cms:publish-migrated-wp-pages`.
 */
return {$export};

PHP;
        File::put(config_path('cms_migrated_pages.php'), $contents);
    }
}
