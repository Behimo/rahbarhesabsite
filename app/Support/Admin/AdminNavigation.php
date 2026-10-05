<?php

namespace App\Support\Admin;

use App\Models\Category;
use Illuminate\Http\Request;

class AdminNavigation
{
    /**
     * @return array{url: string, label: string}|null
     */
    public static function back(Request $request, ?string $override = null): ?array
    {
        $name = (string) $request->route()?->getName();

        if ($name === '' || $name === 'admin.login') {
            return null;
        }

        $override = trim((string) $override);

        if ($override !== '' && ! self::samePlace($request, $override)) {
            return ['url' => $override, 'label' => 'بازگشت'];
        }

        if ($name === 'admin.dashboard') {
            return ['url' => route('home'), 'label' => 'بازگشت به سایت'];
        }

        if ($name === 'admin.posts.index' && $request->boolean('trashed')) {
            return ['url' => route('admin.posts.index'), 'label' => 'بازگشت'];
        }

        $toIndex = [
            'admin.pages.create' => 'admin.pages.index',
            'admin.pages.edit' => 'admin.pages.index',
            'admin.pages.builder' => 'admin.pages.index',
            'admin.posts.create' => 'admin.posts.index',
            'admin.posts.edit' => 'admin.posts.index',
            'admin.courses.create' => 'admin.courses.index',
            'admin.courses.edit' => 'admin.courses.index',
            'admin.products.create' => 'admin.products.index',
            'admin.products.edit' => 'admin.products.index',
            'admin.coupons.create' => 'admin.coupons.index',
            'admin.coupons.edit' => 'admin.coupons.index',
            'admin.users.create' => 'admin.users.index',
            'admin.users.edit' => 'admin.users.index',
            'admin.roles.create' => 'admin.roles.index',
            'admin.roles.edit' => 'admin.roles.index',
            'admin.menus.create' => 'admin.menus.index',
            'admin.menus.edit' => 'admin.menus.index',
            'admin.orders.show' => 'admin.orders.index',
            'admin.licenses.show' => 'admin.licenses.index',
            'admin.messages.show' => 'admin.messages.index',
        ];

        if (isset($toIndex[$name])) {
            return ['url' => route($toIndex[$name]), 'label' => 'بازگشت'];
        }

        if ($name === 'admin.pages.revisions') {
            return ['url' => route('admin.pages.builder', $request->route('page')), 'label' => 'بازگشت'];
        }

        if ($name === 'admin.posts.revisions') {
            return ['url' => route('admin.posts.edit', $request->route('post')), 'label' => 'بازگشت'];
        }

        if (in_array($name, ['admin.categories.create', 'admin.categories.edit'], true)) {
            $category = $request->route('category');
            $type = $category instanceof Category
                ? $category->type
                : ($request->string('type')->toString() === Category::TYPE_PRODUCT ? Category::TYPE_PRODUCT : Category::TYPE_POST);

            return ['url' => route('admin.categories.index', ['type' => $type]), 'label' => 'بازگشت'];
        }

        return ['url' => route('admin.dashboard'), 'label' => 'بازگشت'];
    }

    private static function samePlace(Request $request, string $url): bool
    {
        return rtrim($url, '/') === rtrim($request->url(), '/')
            || rtrim($url, '/') === rtrim($request->fullUrl(), '/');
    }
}
