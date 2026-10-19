<?php

namespace App\Services;

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
     * Production / SEO: configured APP_URL.
     * Local (artisan serve on :8000, Laragon, etc.): current request host so menus/links keep the port.
     */
    public function rootUrl(): string
    {
        if (app()->environment('production')) {
            return rtrim((string) config('app.url'), '/');
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
        $path = '/'.ltrim(trim($path), '/');

        if ($path === '/') {
            return '/';
        }

        // Files with extensions keep their form (sitemaps, robots, assets).
        if (preg_match('/\.[a-zA-Z0-9]{1,8}$/', $path)) {
            return rtrim($path, '/');
        }

        if (config('seo.trailing_slash', true) && ! str_ends_with($path, '/')) {
            $path .= '/';
        }

        return $path;
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
}
