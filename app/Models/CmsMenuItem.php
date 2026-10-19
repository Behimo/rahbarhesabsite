<?php

namespace App\Models;

use App\Services\PermalinkService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsMenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'label', 'type', 'url',
        'route_name', 'route_params', 'target', 'sort_order', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'route_params' => 'array',
            'meta' => 'array',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(CmsMenu::class, 'menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function resolveUrl(): string
    {
        $permalinks = app(PermalinkService::class);

        try {
            return match ($this->type) {
                'route' => $this->resolveRouteUrl($permalinks),
                'page' => isset($this->meta['slug'])
                    ? $permalinks->absolute($permalinks->pageBySlug((string) $this->meta['slug']))
                    : '#',
                'post' => isset($this->meta['slug'])
                    ? $this->resolvePostUrl((string) $this->meta['slug'], $permalinks)
                    : '#',
                'course' => isset($this->meta['slug'])
                    ? $permalinks->absolute('/product/'.$this->meta['slug'])
                    : '#',
                'law' => isset($this->meta['slug'])
                    ? route('laws.show', $this->meta['slug'])
                    : '#',
                default => $this->url ?: '#',
            };
        } catch (\Throwable) {
            return $this->url ?: '#';
        }
    }

    private function resolveRouteUrl(PermalinkService $permalinks): string
    {
        if (! $this->route_name) {
            return '#';
        }

        $params = $this->route_params ?? [];
        $category = isset($params['category']) ? trim((string) $params['category']) : '';

        // Legacy menu rows used courses.index?category=… / blog.index?category=…
        if ($category !== '' && in_array($this->route_name, ['courses.index', 'courses.category'], true)) {
            return $permalinks->absolute('/product-category/'.$category);
        }

        if ($category !== '' && in_array($this->route_name, ['blog.index', 'blog.category'], true)) {
            return $permalinks->absolute('/category/'.$category);
        }

        if ($this->route_name === 'courses.index') {
            return $permalinks->absolute($permalinks->productIndex());
        }

        if ($this->route_name === 'blog.index') {
            return $permalinks->absolute($permalinks->blogIndex());
        }

        if ($this->route_name === 'about') {
            return $permalinks->absolute($permalinks->pageBySlug('about'));
        }

        if ($this->route_name === 'contact') {
            return $permalinks->absolute($permalinks->pageBySlug('contact'));
        }

        if ($this->route_name === 'laws.index') {
            return $permalinks->absolute('/laws');
        }

        $url = route($this->route_name, $params);
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        return $permalinks->absolute($path);
    }

    private function resolvePostUrl(string $slug, PermalinkService $permalinks): string
    {
        $post = CmsPost::query()->where('slug', $slug)->with('categories')->first();

        return $post ? $permalinks->postAbsolute($post) : $permalinks->absolute('/blog/'.$slug);
    }
}
