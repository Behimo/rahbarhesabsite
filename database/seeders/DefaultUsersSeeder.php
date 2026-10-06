<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\AccessCatalog;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class DefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $isProduction = app()->environment('production');

        $adminEmail = mb_strtolower(trim((string) config('cms.admin_email', 'admin@rahbarhesab.ir')));
        $adminPassword = trim((string) config('cms.admin_password'));

        if ($adminEmail === '') {
            $this->command?->error('CMS_ADMIN_EMAIL تنظیم نشده است.');

            throw new RuntimeException('CMS_ADMIN_EMAIL تنظیم نشده است.');
        }

        if (! $isProduction && $adminPassword === '') {
            $adminPassword = 'password';
        }

        $existingAdmin = User::query()->where('email', $adminEmail)->first();

        if ($isProduction && ! $existingAdmin && Str::length($adminPassword) < 12) {
            $message = $adminPassword === ''
                ? 'CMS_ADMIN_PASSWORD در production خالی است — ساخت کاربران پیش‌فرض متوقف شد.'
                : 'CMS_ADMIN_PASSWORD در production باید حداقل ۱۲ کاراکتر باشد.';

            $this->command?->error($message);

            throw new RuntimeException($message);
        }

        $accounts = [
            [
                'email' => $adminEmail,
                'name' => 'مدیر سایت',
                'phone' => '09120000000',
                'password' => $adminPassword,
                'role' => AccessCatalog::ROLE_ADMIN,
            ],
            [
                'email' => 'editor@rahbarhesab.ir',
                'name' => 'ویرایشگر نمونه',
                'phone' => '09120000002',
                'password' => 'password',
                'role' => AccessCatalog::ROLE_EDITOR,
                'demo' => true,
            ],
            [
                'email' => 'shop@rahbarhesab.ir',
                'name' => 'مدیر فروش نمونه',
                'phone' => '09120000003',
                'password' => 'password',
                'role' => AccessCatalog::ROLE_SHOP_MANAGER,
                'demo' => true,
            ],
            [
                'email' => 'instructor@example.com',
                'name' => 'مدرس نمونه',
                'phone' => '09120000004',
                'password' => 'password',
                'role' => AccessCatalog::ROLE_INSTRUCTOR,
                'demo' => true,
            ],
            [
                'email' => 'demo@example.com',
                'name' => 'کاربر نمونه',
                'phone' => '09121111111',
                'password' => 'password',
                'role' => AccessCatalog::ROLE_USER,
                'demo' => true,
            ],
        ];

        foreach ($accounts as $account) {
            $role = $account['role'];
            $isDemo = $account['demo'] ?? false;
            unset($account['role'], $account['demo']);

            if ($isProduction && $isDemo) {
                continue;
            }

            $phone = PhoneNormalizer::toLocal($account['phone']);
            $existing = User::query()->where('email', $account['email'])->first();

            if ($isProduction && $existing) {
                $existing->syncRoles([$role]);

                continue;
            }

            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'phone' => $phone,
                    'mobile' => $phone,
                    'password' => $account['password'],
                    'status' => 'active',
                ]
            );

            $user->syncRoles([$role]);
        }
    }
}
