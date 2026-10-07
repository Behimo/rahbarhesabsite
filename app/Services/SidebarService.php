<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CmsPost;
use App\Models\CmsSidebar;
use App\Models\Order;
use App\Models\ShopProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SidebarService
{
    public function __construct(private PopupService $pages) {}

    /**
     * Public pages a sidebar can sit beside. The user panel already has its own menu.
     *
     * @return array<string, array<string, string>>
     */
    public function pageGroups(): array
    {
        $groups = $this->pages->pageGroups();
        unset($groups['پنل کاربر']);

        return $groups;
    }

    public function isAllowedPageKey(string $key): bool
    {
        if (str_starts_with($key, 'panel.')) {
            return false;
        }

        return $this->pages->isAllowedPageKey($key);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function current(?Request $request = null): Collection
    {
        if (! Schema::hasTable('cms_sidebars')) {
            return collect();
        }

        $request ??= request();

        return CmsSidebar::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (CmsSidebar $sidebar) => $this->matches($sidebar, $request))
            ->map(fn (CmsSidebar $sidebar) => $this->present($sidebar))
            ->filter(fn (array $widget) => $widget['items'] !== [])
            ->values();
    }

    public function matches(CmsSidebar $sidebar, Request $request): bool
    {
        if ($sidebar->target_mode === CmsSidebar::MODE_RULES) {
            return $this->ruleMatches($sidebar, $request);
        }

        return $this->pageMatches($sidebar, $request);
    }

    /**
     * @return array{id: int, title: string, more_url: ?string, items: list<array<string, mixed>>}
     */
    private function present(CmsSidebar $sidebar): array
    {
        return [
            'id' => $sidebar->id,
            'title' => $sidebar->heading(),
            'more_url' => $this->moreUrl($sidebar),
            'items' => $this->items($sidebar),
        ];
    }

    /**
     * @return list<array{title: string, url: ?string, image: ?string, meta: ?string}>
     */
    private function items(CmsSidebar $sidebar): array
    {
        $limit = max(1, min(12, (int) $sidebar->limit));

        if ($sidebar->source === CmsSidebar::SOURCE_POST) {
            return $this->posts($sidebar, $limit);
        }

        return $this->products($sidebar, $limit);
    }

    /**
     * @return list<array{title: string, url: ?string, image: ?string, meta: ?string}>
     */
    private function products(CmsSidebar $sidebar, int $limit): array
    {
        $query = ShopProduct::query()->published();

        if ($sidebar->source === CmsSidebar::SOURCE_COURSE) {
            $query->where('type', ShopProduct::TYPE_COURSE);
        } else {
            $query->where('type', '!=', ShopProduct::TYPE_COURSE);
        }

        if ($sidebar->selection === CmsSidebar::SELECTION_CATEGORY) {
            $ids = $this->categoryIds($sidebar->category_id);
            if ($ids === []) {
                return [];
            }

            $query->whereHas('categories', fn ($related) => $related->whereIn('categories.id', $ids));
        }

        if ($sidebar->selection === CmsSidebar::SELECTION_BESTSELLER) {
            $query->withSum([
                'orderItems as units_sold' => fn ($items) => $items->whereHas(
                    'order',
                    fn ($order) => $order->where('status', Order::STATUS_PAID)
                ),
            ], 'quantity')->orderByDesc('units_sold');
        } else {
            $query->orderByDesc('created_at');
        }

        return $query
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (ShopProduct $product) {
                $url = $product->isCourse() ? route('courses.show', $product->slug) : null;

                return [
                    'title' => $product->title,
                    'url' => $url,
                    'image' => $this->imageUrl($product->featured_image),
                    'meta' => $product->isFree()
                        ? 'رایگان'
                        : fa_digits(number_format($product->effectivePrice())).' تومان',
                ];
            })
            ->all();
    }

    /**
     * @return list<array{title: string, url: ?string, image: ?string, meta: ?string}>
     */
    private function posts(CmsSidebar $sidebar, int $limit): array
    {
        $query = CmsPost::query()->published();

        if ($sidebar->selection === CmsSidebar::SELECTION_CATEGORY) {
            $ids = $this->categoryIds($sidebar->category_id);
            if ($ids === []) {
                return [];
            }

            $query->whereHas('categories', fn ($related) => $related->whereIn('categories.id', $ids));
        }

        if ($sidebar->selection === CmsSidebar::SELECTION_BESTSELLER) {
            $query->orderByDesc('views');
        } else {
            $query->orderByDesc('published_at');
        }

        return $query
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (CmsPost $post) => [
                'title' => $post->title,
                'url' => route('blog.show', $post->slug),
                'image' => $this->imageUrl($post->featured_image),
                'meta' => $post->published_at ? fa_date($post->published_at) : null,
            ])
            ->all();
    }

    private function moreUrl(CmsSidebar $sidebar): ?string
    {
        $category = $sidebar->selection === CmsSidebar::SELECTION_CATEGORY ? $sidebar->category : null;
        $categoryPath = $category?->full_slug;

        if ($sidebar->source === CmsSidebar::SOURCE_POST) {
            return $categoryPath
                ? route('blog.index', ['category' => $categoryPath])
                : route('blog.index');
        }

        if ($sidebar->source === CmsSidebar::SOURCE_COURSE) {
            return $categoryPath
                ? route('courses.index', ['category' => $categoryPath])
                : route('courses.index');
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private function categoryIds(?int $categoryId): array
    {
        if (! $categoryId) {
            return [];
        }

        $category = Category::query()->find($categoryId);
        if (! $category) {
            return [];
        }

        return $category->descendantsAndSelf()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function imageUrl(?string $image): ?string
    {
        $image = trim((string) $image);
        if ($image === '') {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '/')) {
            return $image;
        }

        return asset($image);
    }

    private function pageMatches(CmsSidebar $sidebar, Request $request): bool
    {
        $selected = $sidebar->pages ?? [];
        $route = $request->route()?->getName();

        if ($route && in_array($route, $selected, true)) {
            return true;
        }

        if ($route !== 'pages.show') {
            return false;
        }

        $slug = (string) $request->route('slug');

        return $slug !== '' && in_array('page:'.$slug, $selected, true);
    }

    private function ruleMatches(CmsSidebar $sidebar, Request $request): bool
    {
        $rules = $sidebar->rules ?? [];
        $match = $rules['match'] ?? 'all';
        $current = $this->requestPath($request);
        $needle = $this->normalizePath((string) ($rules['path'] ?? ''));

        return match ($match) {
            'contains' => $needle !== '' && str_contains($current, $needle),
            'starts' => $needle !== '' && ($current === $needle || str_starts_with($current, $needle.'/')),
            'equals' => $needle !== '' && $current === $needle,
            default => true,
        };
    }

    private function requestPath(Request $request): string
    {
        $path = trim($request->path(), '/');

        return $path === '' ? '/' : '/'.$path;
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '/') {
            return $path === '/' ? '/' : '';
        }

        if (str_contains($path, '://')) {
            $path = (string) (parse_url($path, PHP_URL_PATH) ?: '');
        }

        $path = '/'.trim($path, '/');

        return $path === '/' ? '/' : $path;
    }
}
