<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsPopup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PopupService
{
    /**
     * Fixed public routes an admin can target directly.
     *
     * @var array<string, string>
     */
    public const ROUTES = [
        'home' => 'صفحه اصلی',
        'about' => 'درباره ما',
        'contact' => 'تماس با ما',
        'courses.index' => 'فهرست دوره‌ها',
        'courses.show' => 'صفحه هر دوره',
        'blog.index' => 'فهرست مقالات',
        'blog.show' => 'صفحه هر مقاله',
        'search' => 'جستجو',
        'cart.index' => 'سبد خرید',
        'checkout.index' => 'تسویه حساب',
        'login' => 'ورود',
        'panel.dashboard' => 'پیشخوان پنل',
        'panel.courses' => 'دوره‌های پنل',
        'panel.orders' => 'سفارش‌های پنل',
        'panel.profile' => 'پروفایل پنل',
    ];

    /**
     * @return array<string, array<string, string>>
     */
    public function pageGroups(): array
    {
        $panel = ['panel.dashboard', 'panel.courses', 'panel.orders', 'panel.profile'];

        $groups = [
            'صفحات سایت' => array_diff_key(self::ROUTES, array_flip($panel)),
            'پنل کاربر' => array_intersect_key(self::ROUTES, array_flip($panel)),
            'صفحات سفارشی' => ['pages.show' => 'همه صفحات سفارشی'],
        ];

        if (Schema::hasTable('cms_pages')) {
            $custom = CmsPage::query()
                ->orderBy('title')
                ->get()
                ->mapWithKeys(fn (CmsPage $page) => ['page:'.$page->slug => $page->title])
                ->all();

            $groups['صفحات سفارشی'] += $custom;
        }

        return $groups;
    }

    public function isAllowedPageKey(string $key): bool
    {
        if (isset(self::ROUTES[$key]) || $key === 'pages.show') {
            return true;
        }

        if (! str_starts_with($key, 'page:')) {
            return false;
        }

        $slug = substr($key, 5);

        return $slug !== '' && CmsPage::query()->where('slug', $slug)->exists();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function current(?Request $request = null): ?array
    {
        if (! Schema::hasTable('cms_popups')) {
            return null;
        }

        $request ??= request();

        $popup = CmsPopup::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->first(fn (CmsPopup $popup) => $this->matches($popup, $request));

        return $popup ? $this->present($popup) : null;
    }

    public function matches(CmsPopup $popup, Request $request): bool
    {
        if (! $this->audienceMatches($popup, $request)) {
            return false;
        }

        if ($popup->target_mode === CmsPopup::MODE_RULES) {
            return $this->ruleMatches($popup, $request);
        }

        return $this->pageMatches($popup, $request);
    }

    public static function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?: ''));
        if (! in_array($scheme, ['http', 'https'], true) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $url;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(CmsPopup $popup): array
    {
        $buttonUrl = filled($popup->button_label) ? self::safeUrl($popup->button_url) : null;

        return [
            'id' => $popup->id,
            'version' => $popup->updated_at?->getTimestamp() ?? $popup->id,
            'title' => $popup->title,
            'body' => $popup->body,
            'image' => self::safeUrl($popup->image_url),
            'button_label' => $buttonUrl ? $popup->button_label : null,
            'button_url' => $buttonUrl,
            'delay' => max(0, min(300, (int) $popup->delay_seconds)),
            'frequency' => array_key_exists($popup->frequency, CmsPopup::FREQUENCIES) ? $popup->frequency : 'session',
        ];
    }

    private function audienceMatches(CmsPopup $popup, Request $request): bool
    {
        return match ($popup->audience) {
            'guest' => $request->user() === null,
            'auth' => $request->user() !== null,
            default => true,
        };
    }

    private function pageMatches(CmsPopup $popup, Request $request): bool
    {
        $selected = $popup->pages ?? [];
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

    private function ruleMatches(CmsPopup $popup, Request $request): bool
    {
        $rules = $popup->rules ?? [];
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
