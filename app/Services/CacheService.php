<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CacheService
{
    public const TAG_CONTENT = 'cms.content';

    public const TAG_MENUS = 'cms.menus';

    public const TAG_TAXONOMY = 'cms.taxonomy';

    public const TAG_COURSES = 'cms.courses';

    public function remember(string $key, int $ttl, callable $callback, ?string $tag = null): mixed
    {
        if ($tag && $this->supportsTags()) {
            return Cache::tags([$tag])->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    public function flushTag(string $tag): void
    {
        if ($this->supportsTags()) {
            Cache::tags([$tag])->flush();
        }
    }

    public function rememberSitemap(string $section, string $root, callable $callback): array
    {
        $version = Cache::get('seo.sitemap.version', 'initial');
        $namespace = hash('sha256', $root.'|'.(int) config('seo.trailing_slash', true).'|'.$version);

        return Cache::remember('seo.sitemap.'.$namespace.'.'.$section, (int) config('seo.sitemap_cache_ttl', 300), $callback);
    }

    public function forgetSitemaps(): void
    {
        Cache::forever('seo.sitemap.version', Str::random(20));
    }

    public function flushContent(): void
    {
        $this->forgetSitemaps();
        foreach ([self::TAG_CONTENT, self::TAG_MENUS, self::TAG_TAXONOMY, self::TAG_COURSES] as $tag) {
            $this->flushTag($tag);
        }

        Cache::forget('cms_products');
        Cache::forget('cms_home_content');
    }

    private function supportsTags(): bool
    {
        return in_array(config('cache.default'), ['redis', 'memcached', 'dynamodb'], true);
    }
}
