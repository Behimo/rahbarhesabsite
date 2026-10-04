<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessCatalog
{
    public const ROLE_USER = 'user';

    public const ROLE_INSTRUCTOR = 'instructor';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_SHOP_MANAGER = 'shop_manager';

    public const ROLE_ADMIN = 'admin';

    public const ACCESS_ADMIN = 'access_admin';

    public const MANAGE_USERS = 'manage_users';

    public const MANAGE_COURSES = 'manage_courses';

    public const MANAGE_ORDERS = 'manage_orders';

    public const MANAGE_POSTS = 'manage_posts';

    public const MANAGE_PAGES = 'manage_pages';

    public const MANAGE_SETTINGS = 'manage_settings';

    public const MANAGE_MENUS = 'manage_menus';

    public const MANAGE_TAXONOMIES = 'manage_taxonomies';

    public const MANAGE_MEDIA = 'manage_media';

    public const MANAGE_MESSAGES = 'manage_messages';

    public const MANAGE_IMPORT = 'manage_import';

    public static function guard(): string
    {
        return (string) config('auth.defaults.guard', 'web');
    }

    public static function roles(): array
    {
        return [
            self::ROLE_USER => 'کاربر عادی',
            self::ROLE_INSTRUCTOR => 'مدرس',
            self::ROLE_EDITOR => 'ویرایشگر محتوا',
            self::ROLE_SHOP_MANAGER => 'مدیر فروشگاه',
            self::ROLE_ADMIN => 'مدیر سیستم',
        ];
    }

    public static function labels(): array
    {
        return [
            self::ACCESS_ADMIN => 'ورود به پنل مدیریت',
            self::MANAGE_USERS => 'مدیریت کاربران',
            self::MANAGE_COURSES => 'مدیریت دوره‌ها',
            self::MANAGE_ORDERS => 'مدیریت سفارش‌ها',
            self::MANAGE_POSTS => 'مدیریت بلاگ و محصولات',
            self::MANAGE_PAGES => 'مدیریت صفحات',
            self::MANAGE_SETTINGS => 'تنظیمات سایت',
            self::MANAGE_MENUS => 'مدیریت منوها',
            self::MANAGE_TAXONOMIES => 'دسته‌بندی‌ها',
            self::MANAGE_MEDIA => 'مدیریت رسانه',
            self::MANAGE_MESSAGES => 'پیام‌های تماس',
            self::MANAGE_IMPORT => 'درون‌ریزی و برون‌بری',
        ];
    }

    public static function rolePermissions(string $role): array
    {
        $staff = [self::ACCESS_ADMIN];

        return match ($role) {
            self::ROLE_ADMIN => array_keys(self::labels()),
            self::ROLE_SHOP_MANAGER => [...$staff, self::MANAGE_ORDERS, self::MANAGE_COURSES],
            self::ROLE_EDITOR => [...$staff, self::MANAGE_POSTS, self::MANAGE_PAGES, self::MANAGE_MEDIA, self::MANAGE_MENUS, self::MANAGE_TAXONOMIES, self::MANAGE_MESSAGES],
            self::ROLE_INSTRUCTOR => [...$staff, self::MANAGE_COURSES, self::MANAGE_MEDIA],
            default => [],
        };
    }

    public static function routeMap(): array
    {
        return [
            'admin.users.*' => self::MANAGE_USERS,
            'admin.courses.*' => self::MANAGE_COURSES,
            'admin.orders.*' => self::MANAGE_ORDERS,
            'admin.licenses.*' => self::MANAGE_ORDERS,
            'admin.coupons.*' => self::MANAGE_ORDERS,
            'admin.posts.*' => self::MANAGE_POSTS,
            'admin.products.*' => self::MANAGE_POSTS,
            'admin.pages.*' => self::MANAGE_PAGES,
            'admin.home.*' => self::MANAGE_PAGES,
            'admin.settings.*' => self::MANAGE_SETTINGS,
            'admin.gateways.*' => self::MANAGE_SETTINGS,
            'admin.redirects.*' => self::MANAGE_SETTINGS,
            'admin.menus.*' => self::MANAGE_MENUS,
            'admin.tags.*' => self::MANAGE_TAXONOMIES,
            'admin.categories.*' => self::MANAGE_TAXONOMIES,
            'admin.media.*' => self::MANAGE_MEDIA,
            'admin.messages.*' => self::MANAGE_MESSAGES,
            'admin.export' => self::MANAGE_IMPORT,
            'admin.import' => self::MANAGE_IMPORT,
            'admin.dashboard' => self::ACCESS_ADMIN,
            'admin.search' => self::ACCESS_ADMIN,
        ];
    }

    public static function permissionForRoute(?string $routeName): string
    {
        if ($routeName) {
            foreach (self::routeMap() as $pattern => $permission) {
                if (str_ends_with($pattern, '.*')) {
                    $prefix = substr($pattern, 0, -2);
                    if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
                        return $permission;
                    }
                } elseif ($routeName === $pattern) {
                    return $permission;
                }
            }
        }

        return self::ACCESS_ADMIN;
    }

    public static function allows(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        try {
            return $user->can($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public static function install(): void
    {
        $guard = self::guard();

        foreach (array_keys(self::labels()) as $name) {
            Permission::findOrCreate($name, $guard);
        }

        foreach (array_keys(self::roles()) as $name) {
            $role = Role::findOrCreate($name, $guard);
            $role->syncPermissions(self::rolePermissions($name));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
