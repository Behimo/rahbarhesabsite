<?php

namespace App\Services;

use App\Models\CafeQuestion;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use App\Models\Tag;

class PermalinkService
{
    public function absolute(string $path): string
    {
        $path = $this->normalizePath($path);
        $base = rtrim($this->rootUrl(), '/');

        return $base === '' ? $path : $base.$path;
    }

    /**
     * Production / SEO: configured canonical host and scheme policy.
     * Local (artisan serve on :8000, Laragon, etc.): current request host so menus/links keep the port.
     */
    public function rootUrl(): string
    {
        if (app()->environment('production')) {
            $configured = (string) config('app.url');
            $host = preg_replace('/^www\./i', '', (string) config('seo.canonical_host', parse_url($configured, PHP_URL_HOST)));
            $host = config('seo.force_www', false) ? 'www.'.$host : $host;
            $scheme = config('seo.force_https', true) ? 'https' : (parse_url($configured, PHP_URL_SCHEME) ?: 'https');

            return $scheme.'://'.$host;
        }

        try {
            if (! app()->runningInConsole() && request()) {
                return rtrim(request()->getSchemeAndHttpHost(), '/');
            }
        } catch (\Throwable) {
            // fall through
        }

        return rtrim((string) config('app.url'), '/');
    }

    public function normalizePath(string $path): string
    {
        preg_match('/^([^?#]*)(.*)$/s', trim($path), $parts);
        $path = '/'.ltrim($parts[1], '/');
        $suffix = $parts[2];
        $path = rtrim($path, '/') ?: '/';

        if ($path !== '/' && ! $this->isSystemPath($path) && ! $this->isFilePath($path) && config('seo.trailing_slash', true)) {
            $path .= '/';
        }

        return $path.$suffix;
    }

    public function isFilePath(string $path): bool
    {
        return (bool) preg_match('/\.[a-zA-Z0-9]{1,8}$/', rtrim($path, '/'));
    }

    public function isSystemPath(string $path): bool
    {
        $first = mb_strtolower(explode('/', ltrim(rawurldecode($path), '/'), 2)[0]);

        return in_array($first, config('seo.system_prefixes', []), true);
    }

    public function post(CmsPost $post): string
    {
        $category = $post->category;
        $categorySlug = $category?->slug ?: 'blog';

        return $this->normalizePath('/'.$categorySlug.'/'.$post->slug);
    }

    public function postAbsolute(CmsPost $post): string
    {
        return $this->absolute($this->post($post));
    }

    public function question(CafeQuestion $question): string
    {
        return $this->normalizePath('/questions/'.$question->slug);
    }

    public function questionAbsolute(CafeQuestion $question): string
    {
        return $this->absolute($this->question($question));
    }

    public function blogIndex(?int $page = null): string
    {
        if ($page && $page > 1) {
            return $this->normalizePath('/blog/page/'.$page);
        }

        return $this->normalizePath('/blog');
    }

    public function category(Category $category, ?int $page = null): string
    {
        $slug = $category->full_slug ?: $category->slug;

        if ($category->type === Category::TYPE_PRODUCT) {
            $base = '/product-category/'.$slug;
        } else {
            $base = '/category/'.$slug;
        }

        if ($page && $page > 1) {
            $base .= '/page/'.$page;
        }

        return $this->normalizePath($base);
    }

    public function tag(Tag $tag, ?int $page = null): string
    {
        $base = '/tag/'.$tag->slug;

        if ($page && $page > 1) {
            $base .= '/page/'.$page;
        }

        return $this->normalizePath($base);
    }

    public function product(ShopProduct $product): string
    {
        return $this->normalizePath('/product/'.$product->slug);
    }

    public function productAbsolute(ShopProduct $product): string
    {
        return $this->absolute($this->product($product));
    }

    public function productIndex(?int $page = null): string
    {
        if ($page && $page > 1) {
            return $this->normalizePath('/product/page/'.$page);
        }

        return $this->normalizePath('/product');
    }

    public function page(CmsPage $page): string
    {
        return match ($page->slug) {
            'home' => '/',
            'about' => $this->normalizePath('/about-us'),
            'contact' => $this->normalizePath('/contact-us'),
            default => $this->normalizePath('/'.$page->slug),
        };
    }

    public function pageAbsolute(CmsPage $page): string
    {
        return $this->absolute($this->page($page));
    }

    public function pageBySlug(string $slug): string
    {
        return match ($slug) {
            'home', '' => '/',
            'about' => $this->normalizePath('/about-us'),
            'contact' => $this->normalizePath('/contact-us'),
            default => $this->normalizePath('/'.$slug),
        };
    }

    /** @return list<string> */
    public function reservedPrefixes(): array
    {
        return config('seo.reserved_prefixes', []);
    }

    public function isReservedPrefix(string $segment): bool
    {
        return in_array(mb_strtolower($segment), array_map('mb_strtolower', $this->reservedPrefixes()), true);
    }

    /**
     * Build an anchored route constraint that accepts any single path segment
     * except the configured reserved names.
     *
     * Only a whole reserved segment is blocked ("build"), never a word that
     * merely starts with one ("builder-page") — the bug this replaces used a
     * non-anchored negative lookahead and 404'd every such page.
     *
     * @param  list<string>  $exclude  reserved names to allow here (e.g. "blog" for post categories)
     */
    public function reservedSegmentPattern(array $exclude = []): string
    {
        $prefixes = array_values(array_diff(
            array_map('mb_strtolower', $this->reservedPrefixes()),
            array_map('mb_strtolower', $exclude)
        ));

        if ($prefixes === []) {
            return '[^/]+';
        }

        $alternation = implode('|', array_map(
            static fn (string $prefix): string => preg_quote($prefix, '/').'(?:/|$)',
            $prefixes
        ));

        return '^(?!'.$alternation.')[^/]+$';
    }
}
