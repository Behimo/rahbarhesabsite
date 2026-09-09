<?php

namespace App\Support;

class Permission
{
    public const MANAGE_USERS = 'manage_users';

    public const MANAGE_COURSES = 'manage_courses';

    public const MANAGE_ORDERS = 'manage_orders';

    public const MANAGE_POSTS = 'manage_posts';

    public const MANAGE_PAGES = 'manage_pages';

    public const MANAGE_SETTINGS = 'manage_settings';

    public const MANAGE_THEMES = 'manage_themes';

    public const MANAGE_PLUGINS = 'manage_plugins';

    public const MANAGE_MENUS = 'manage_menus';

    public const MANAGE_TAXONOMIES = 'manage_taxonomies';

    public const MANAGE_MEDIA = 'manage_media';

    public static function roleDefaults(string $role): array
    {
        return match ($role) {
            'admin' => array_keys(self::labels()),
            'shop_manager' => [self::MANAGE_ORDERS, self::MANAGE_COURSES],
            'editor' => [self::MANAGE_POSTS, self::MANAGE_PAGES, self::MANAGE_MEDIA, self::MANAGE_MENUS, self::MANAGE_TAXONOMIES],
            'instructor' => [self::MANAGE_COURSES, self::MANAGE_MEDIA],
            default => [],
        };
    }

    public static function labels(): array
    {
        return [
            self::MANAGE_USERS => 'مدیریت کاربران',
            self::MANAGE_COURSES => 'مدیریت دوره‌ها',
            self::MANAGE_ORDERS => 'مدیریت سفارش‌ها',
            self::MANAGE_POSTS => 'مدیریت بلاگ',
            self::MANAGE_PAGES => 'مدیریت صفحات',
            self::MANAGE_SETTINGS => 'تنظیمات سایت',
            self::MANAGE_THEMES => 'مدیریت قالب‌ها',
            self::MANAGE_PLUGINS => 'مدیریت افزونه‌ها',
            self::MANAGE_MENUS => 'مدیریت منوها',
            self::MANAGE_TAXONOMIES => 'دسته‌بندی‌ها',
            self::MANAGE_MEDIA => 'مدیریت رسانه',
        ];
    }

    public static function roles(): array
    {
        return [
            'user' => 'کاربر عادی',
            'instructor' => 'مدرس',
            'editor' => 'ویرایشگر محتوا',
            'shop_manager' => 'مدیر فروشگاه',
            'admin' => 'مدیر سیستم',
        ];
    }

    public static function routeMap(): array
    {
        return [
            'admin.users.*' => self::MANAGE_USERS,
            'admin.courses.*' => self::MANAGE_COURSES,
            'admin.orders.*' => self::MANAGE_ORDERS,
            'admin.posts.*' => self::MANAGE_POSTS,
            'admin.pages.*' => self::MANAGE_PAGES,
            'admin.settings.*' => self::MANAGE_SETTINGS,
            'admin.themes.*' => self::MANAGE_THEMES,
            'admin.plugins.*' => self::MANAGE_PLUGINS,
            'admin.menus.*' => self::MANAGE_MENUS,
            'admin.taxonomies.*' => self::MANAGE_TAXONOMIES,
            'admin.media.*' => self::MANAGE_MEDIA,
            'admin.home.*' => self::MANAGE_PAGES,
            'admin.categories.*' => self::MANAGE_TAXONOMIES,
            'admin.products.*' => self::MANAGE_POSTS,
        ];
    }
}
