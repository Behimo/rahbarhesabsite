<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AccessCatalog
{
    public const ROLE_USER = 'user';

    public const ROLE_INSTRUCTOR = 'instructor';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_SHOP_MANAGER = 'shop_manager';

    public const ROLE_ADMIN = 'admin';

    public const ACCESS_ADMIN = 'access_admin';

    public const VIEW = 'view';

    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

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

    /**
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            self::VIEW => 'نمایش',
            self::CREATE => 'ایجاد',
            self::UPDATE => 'ویرایش',
            self::DELETE => 'حذف',
        ];
    }

    /**
     * @return array<string, array{label: string, actions: array<int|string, string>}>
     */
    public static function groups(): array
    {
        $crud = [self::VIEW, self::CREATE, self::UPDATE, self::DELETE];

        return [
            'users' => ['label' => 'کاربران', 'actions' => $crud],
            'roles' => ['label' => 'نقش‌ها', 'actions' => $crud],
            'courses' => ['label' => 'دوره‌ها', 'actions' => $crud],
            'orders' => ['label' => 'سفارش، لایسنس و تخفیف', 'actions' => $crud],
            'posts' => ['label' => 'بلاگ و محصولات', 'actions' => $crud],
            'pages' => ['label' => 'صفحات', 'actions' => $crud],
            'popups' => ['label' => 'پاپ‌آپ‌ها', 'actions' => $crud],
            'sidebars' => ['label' => 'سایدبارها', 'actions' => $crud],
            'menus' => ['label' => 'منوها', 'actions' => $crud],
            'taxonomies' => ['label' => 'دسته‌بندی و برچسب', 'actions' => $crud],
            'media' => ['label' => 'رسانه', 'actions' => [self::VIEW, self::CREATE, self::DELETE]],
            'messages' => ['label' => 'پیام‌های تماس', 'actions' => [self::VIEW, self::DELETE]],
            'settings' => ['label' => 'تنظیمات، درگاه و ریدایرکت', 'actions' => [self::VIEW, self::UPDATE]],
            'transfer' => [
                'label' => 'درون‌ریزی و برون‌بری',
                'actions' => [
                    self::VIEW => 'برون‌بری',
                    self::CREATE => 'درون‌ریزی',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function groupActions(string $group): array
    {
        $actions = self::groups()[$group]['actions'] ?? [];

        if (array_is_list($actions)) {
            $labels = self::actionLabels();

            return array_combine($actions, array_map(fn (string $action) => $labels[$action], $actions));
        }

        return $actions;
    }

    public static function permission(string $group, string $action): string
    {
        return $action.'_'.$group;
    }

    /**
     * @return list<string>
     */
    public static function permissionsForGroup(string $group): array
    {
        return array_map(
            fn (string $action) => self::permission($group, $action),
            array_keys(self::groupActions($group))
        );
    }

    public static function labels(): array
    {
        $labels = [self::ACCESS_ADMIN => 'ورود به پنل مدیریت'];

        foreach (self::groups() as $group => $meta) {
            foreach (self::groupActions($group) as $action => $actionLabel) {
                $labels[self::permission($group, $action)] = $meta['label'].' — '.$actionLabel;
            }
        }

        return $labels;
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    public static function normalize(array $permissions): array
    {
        $allowed = array_keys(self::labels());
        $permissions = array_values(array_intersect($permissions, $allowed));

        foreach (array_keys(self::groups()) as $group) {
            $view = self::permission($group, self::VIEW);
            if (! in_array($view, $allowed, true)) {
                continue;
            }

            foreach (array_keys(self::groupActions($group)) as $action) {
                if ($action === self::VIEW) {
                    continue;
                }

                if (in_array(self::permission($group, $action), $permissions, true)) {
                    $permissions[] = $view;
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    public static function rolePermissions(string $role): array
    {
        $groups = match ($role) {
            self::ROLE_ADMIN => array_keys(self::groups()),
            self::ROLE_SHOP_MANAGER => ['orders', 'courses'],
            self::ROLE_EDITOR => ['posts', 'pages', 'popups', 'sidebars', 'media', 'menus', 'taxonomies', 'messages'],
            self::ROLE_INSTRUCTOR => ['courses', 'media'],
            default => [],
        };

        if ($groups === []) {
            return [];
        }

        $permissions = [self::ACCESS_ADMIN];
        foreach ($groups as $group) {
            $permissions = [...$permissions, ...self::permissionsForGroup($group)];
        }

        return array_values(array_unique($permissions));
    }

    /**
     * @return array<string, string>
     */
    public static function areas(): array
    {
        return [
            'admin.users' => 'users',
            'admin.roles' => 'roles',
            'admin.courses' => 'courses',
            'admin.orders' => 'orders',
            'admin.licenses' => 'orders',
            'admin.coupons' => 'orders',
            'admin.posts' => 'posts',
            'admin.products' => 'posts',
            'admin.pages' => 'pages',
            'admin.popups' => 'popups',
            'admin.sidebars' => 'sidebars',
            'admin.home' => 'pages',
            'admin.settings' => 'settings',
            'admin.gateways' => 'settings',
            'admin.redirects' => 'settings',
            'admin.menus' => 'menus',
            'admin.tags' => 'taxonomies',
            'admin.categories' => 'taxonomies',
            'admin.media' => 'media',
            'admin.messages' => 'messages',
            'admin.export' => 'transfer',
            'admin.import' => 'transfer',
        ];
    }

    public static function groupForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        foreach (self::areas() as $prefix => $group) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
                return $group;
            }
        }

        return null;
    }

    public static function permissionForRoute(?string $routeName): string
    {
        $group = self::groupForRoute($routeName);
        if ($group === null || $routeName === null) {
            return self::ACCESS_ADMIN;
        }

        $action = self::actionForRoute($routeName);
        $available = array_keys(self::groupActions($group));

        if (! in_array($action, $available, true)) {
            $action = in_array(self::UPDATE, $available, true) ? self::UPDATE : $available[0];
        }

        return self::permission($group, $action);
    }

    public static function allowsMenu(?User $user, string $slug): bool
    {
        if ($slug === 'admin.home' || str_starts_with($slug, 'admin.home.')) {
            return self::allows($user, self::permission('pages', self::UPDATE));
        }

        if (in_array($slug, ['admin.dashboard', 'admin.search'], true)) {
            return self::allows($user, self::ACCESS_ADMIN);
        }

        $group = self::groupForRoute($slug);
        if ($group === null) {
            return true;
        }

        return self::allows($user, self::permission($group, self::VIEW));
    }

    public static function allows(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        try {
            if ($user->can($permission)) {
                return true;
            }
        } catch (PermissionDoesNotExist) {
            return false;
        }

        if (! str_starts_with($permission, self::VIEW.'_')) {
            return false;
        }

        $group = substr($permission, strlen(self::VIEW) + 1);
        if (! isset(self::groups()[$group])) {
            return false;
        }

        foreach (array_keys(self::groupActions($group)) as $action) {
            if ($action === self::VIEW) {
                continue;
            }

            try {
                if ($user->can(self::permission($group, $action))) {
                    return true;
                }
            } catch (PermissionDoesNotExist) {
                continue;
            }
        }

        return false;
    }

    public static function expandLegacyPermissions(): void
    {
        $map = [
            'manage_users' => 'users',
            'manage_courses' => 'courses',
            'manage_orders' => 'orders',
            'manage_posts' => 'posts',
            'manage_pages' => 'pages',
            'manage_settings' => 'settings',
            'manage_menus' => 'menus',
            'manage_taxonomies' => 'taxonomies',
            'manage_media' => 'media',
            'manage_messages' => 'messages',
            'manage_import' => 'transfer',
        ];
        $guard = self::guard();

        foreach (Role::query()->where('guard_name', $guard)->with('permissions')->get() as $role) {
            $current = $role->permissions->pluck('name')->all();
            $grant = [];
            $revoke = [];

            foreach ($map as $legacy => $group) {
                if (! in_array($legacy, $current, true)) {
                    continue;
                }

                $grant = [...$grant, ...self::permissionsForGroup($group)];
                $revoke[] = $legacy;
            }

            if ($grant !== []) {
                $role->givePermissionTo(array_values(array_unique($grant)));
            }

            if ($revoke !== []) {
                $existing = array_values(array_filter(
                    $revoke,
                    fn (string $name) => Permission::query()->where('name', $name)->where('guard_name', $guard)->exists()
                ));

                if ($existing !== []) {
                    $role->revokePermissionTo($existing);
                }
            }
        }

        Permission::query()->where('guard_name', $guard)->whereIn('name', array_keys($map))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private static function actionForRoute(string $routeName): string
    {
        if (str_contains($routeName, '.sections.') || str_contains($routeName, '.lessons.')) {
            return self::UPDATE;
        }

        $overrides = [
            'admin.orders.enroll' => self::CREATE,
            'admin.orders.enrollments.revoke' => self::DELETE,
            'admin.orders.mark-paid' => self::UPDATE,
            'admin.orders.retry-licenses' => self::UPDATE,
            'admin.licenses.reissue' => self::UPDATE,
            'admin.posts.duplicate' => self::CREATE,
            'admin.posts.force-destroy' => self::DELETE,
            'admin.export' => self::VIEW,
            'admin.import' => self::CREATE,
            'admin.settings.gateways' => self::UPDATE,
        ];

        if (isset($overrides[$routeName])) {
            return $overrides[$routeName];
        }

        $leaf = str_contains($routeName, '.') ? substr($routeName, strrpos($routeName, '.') + 1) : $routeName;

        return match ($leaf) {
            'index', 'show', 'preview', 'revisions' => self::VIEW,
            'create', 'store', 'duplicate' => self::CREATE,
            'destroy', 'force-destroy' => self::DELETE,
            default => self::UPDATE,
        };
    }

    public static function roleLabel(?string $name, ?string $label = null): string
    {
        if (filled($label)) {
            return $label;
        }

        if ($name && isset(self::roles()[$name])) {
            return self::roles()[$name];
        }

        return $name ?: '—';
    }

    /**
     * @return Collection<int, Role>
     */
    public static function assignableRoles(): Collection
    {
        return Role::query()
            ->where('guard_name', self::guard())
            ->orderByDesc('is_system')
            ->orderBy('label')
            ->orderBy('name')
            ->get();
    }

    public static function install(): void
    {
        $guard = self::guard();
        $hasMeta = Schema::hasTable('roles') && Schema::hasColumn('roles', 'label');

        foreach (array_keys(self::labels()) as $name) {
            Permission::findOrCreate($name, $guard);
        }

        foreach (self::roles() as $name => $label) {
            $role = Role::findOrCreate($name, $guard);

            if ($hasMeta) {
                $role->is_system = true;
                if (! filled($role->label)) {
                    $role->label = $label;
                }
                $role->save();
            }

            $role->load('permissions');
            $missing = array_values(array_diff(
                self::rolePermissions($name),
                $role->permissions->pluck('name')->all(),
            ));

            if ($missing !== []) {
                $role->givePermissionTo($missing);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
