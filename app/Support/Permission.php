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

    public static function roleDefaults(string $role): array
    {
        return match ($role) {
            'admin' => [
                self::MANAGE_USERS,
                self::MANAGE_COURSES,
                self::MANAGE_ORDERS,
                self::MANAGE_POSTS,
                self::MANAGE_PAGES,
                self::MANAGE_SETTINGS,
            ],
            'shop_manager' => [self::MANAGE_ORDERS, self::MANAGE_COURSES],
            'editor' => [self::MANAGE_POSTS, self::MANAGE_PAGES],
            'instructor' => [self::MANAGE_COURSES],
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
}
