<?php

namespace App\Services;

use App\Models\CmsMenu;
use App\Models\CmsMenuItem;
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
        $items = $menu->allItems()->with('children')->get()->keyBy('id');

        return $items
            ->whereNull('parent_id')
            ->sortBy('sort_order')
            ->map(fn (CmsMenuItem $item) => $this->itemToArray($item, $items))
            ->values()
            ->all();
    }

    public function saveTree(CmsMenu $menu, array $tree): void
    {
        $menu->allItems()->delete();
        $this->persistItems($menu, $tree);
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

        return collect($this->buildTree($menu))->map(function (array $item) {
            return [
                'label' => $item['label'],
                'href' => $item['url'],
                'target' => $item['target'],
                'children' => $item['children'] ?? [],
            ];
        })->all();
    }

    private function itemToArray(CmsMenuItem $item, $all): array
    {
        $children = $all->where('parent_id', $item->id)
            ->sortBy('sort_order')
            ->map(fn (CmsMenuItem $child) => $this->itemToArray($child, $all))
            ->values()
            ->all();

        return [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $item->resolveUrl(),
            'target' => $item->target,
            'children' => $children,
        ];
    }

    private function persistItems(CmsMenu $menu, array $tree, ?int $parentId = null, int $order = 0): void
    {
        foreach ($tree as $node) {
            $item = $menu->allItems()->create([
                'parent_id' => $parentId,
                'label' => $node['label'],
                'type' => $node['type'] ?? 'custom',
                'url' => $node['url'] ?? null,
                'route_name' => $node['route_name'] ?? null,
                'route_params' => $node['route_params'] ?? null,
                'target' => $node['target'] ?? '_self',
                'sort_order' => $order++,
                'meta' => $node['meta'] ?? null,
            ]);

            if (! empty($node['children'])) {
                $this->persistItems($menu, $node['children'], $item->id);
            }
        }
    }
}
