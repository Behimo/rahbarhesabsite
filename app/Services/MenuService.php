<?php

namespace App\Services;

use App\Models\CmsMenu;
use App\Models\CmsMenuItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MenuService
{
    public function __construct(private CacheService $cache) {}

    public function linksForLocation(string $location): array
    {
        return $this->cache->remember(
            "cms.menu.{$location}",
            3600,
            fn () => $this->buildLinks($location),
            CacheService::TAG_MENUS
        );
    }

    public function buildTree(CmsMenu $menu): array
    {
        $items = $menu->allItems()->get()->keyBy('id');

        return $items
            ->whereNull('parent_id')
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->map(fn (CmsMenuItem $item) => $this->itemToEditorArray($item, $items))
            ->values()
            ->all();
    }

    public function saveTree(CmsMenu $menu, array $tree): void
    {
        $menu->allItems()->update(['parent_id' => null]);
        $menu->allItems()->delete();
        $this->persistItems($menu, $tree);
        $this->forgetLocation($menu->location);
    }

    public function forgetLocation(?string $location): void
    {
        if ($location) {
            Cache::forget("cms.menu.{$location}");
        }

        $this->cache->flushTag(CacheService::TAG_MENUS);
    }

    private function buildLinks(string $location): array
    {
        if (! Schema::hasTable('cms_menus')) {
            return [];
        }

        $menu = CmsMenu::query()->where('location', $location)->first();

        if (! $menu) {
            return [];
        }

        $items = $menu->allItems()->get()->keyBy('id');

        return $items
            ->whereNull('parent_id')
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->map(fn (CmsMenuItem $item) => $this->itemToLinkArray($item, $items))
            ->values()
            ->all();
    }

    private function itemToEditorArray(CmsMenuItem $item, $all): array
    {
        return [
            'label' => $item->label,
            'type' => $item->type ?: 'custom',
            'url' => $item->url,
            'route_name' => $item->route_name,
            'route_params' => $item->route_params,
            'target' => $item->target ?: '_self',
            'meta' => [
                'slug' => $item->meta['slug'] ?? '',
            ],
            'children' => $this->childItems($all, $item->id)
                ->map(fn (CmsMenuItem $child) => $this->itemToEditorArray($child, $all))
                ->values()
                ->all(),
        ];
    }

    private function itemToLinkArray(CmsMenuItem $item, $all): array
    {
        $children = $this->childItems($all, $item->id)
            ->map(fn (CmsMenuItem $child) => $this->itemToLinkArray($child, $all))
            ->values()
            ->all();

        $link = [
            'label' => $item->label,
            'href' => $item->resolveUrl(),
            'target' => $item->target ?: '_self',
        ];

        if ($children !== []) {
            $link['children'] = $children;
        }

        return $link;
    }

    private function childItems($all, int $parentId)
    {
        return $all
            ->filter(fn (CmsMenuItem $item) => (int) $item->parent_id === $parentId)
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']]);
    }

    private function persistItems(CmsMenu $menu, array $tree, ?int $parentId = null): void
    {
        $order = 0;

        foreach ($tree as $node) {
            if (! is_array($node)) {
                continue;
            }

            $type = $node['type'] ?? 'custom';
            if (! in_array($type, ['custom', 'route', 'page', 'post', 'course'], true)) {
                $type = 'custom';
            }

            $url = null;
            $routeName = null;
            $routeParams = null;
            $meta = null;

            if ($type === 'custom') {
                $url = ($node['url'] ?? null) !== '' ? ($node['url'] ?? null) : null;
            } elseif ($type === 'route') {
                $routeName = ($node['route_name'] ?? null) !== '' ? ($node['route_name'] ?? null) : null;
                $routeParams = is_array($node['route_params'] ?? null) ? $node['route_params'] : null;
            } else {
                $slug = trim((string) ($node['meta']['slug'] ?? ''));
                $meta = $slug !== '' ? ['slug' => $slug] : null;
            }

            $item = $menu->allItems()->create([
                'parent_id' => $parentId,
                'label' => trim((string) ($node['label'] ?? '')),
                'type' => $type,
                'url' => $url,
                'route_name' => $routeName,
                'route_params' => $routeParams,
                'target' => ($node['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                'sort_order' => $order++,
                'meta' => $meta,
            ]);

            if (! empty($node['children']) && is_array($node['children'])) {
                $this->persistItems($menu, $node['children'], $item->id);
            }
        }
    }
}
