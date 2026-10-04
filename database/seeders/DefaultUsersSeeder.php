<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\AccessCatalog;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Seeder;

class DefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $adminEmail = mb_strtolower(trim((string) config('cms.admin_email', 'admin@rahbarhesab.ir')));
        $adminPassword = trim((string) config('cms.admin_password'));
        if ($adminPassword === '') {
            $adminPassword = 'password';
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
            ],
            [
                'email' => 'shop@rahbarhesab.ir',
                'name' => 'مدیر فروش نمونه',
                'phone' => '09120000003',
                'password' => 'password',
                'role' => AccessCatalog::ROLE_SHOP_MANAGER,
            ],
            [
                'email' => 'instructor@example.com',
                'name' => 'مدرس نمونه',
                'phone' => '09120000004',
                'password' => 'password',
                'role' => AccessCatalog::ROLE_INSTRUCTOR,
            ],
            [
                'email' => 'demo@example.com',
                'name' => 'کاربر نمونه',
                'phone' => '09121111111',
                'password' => 'password',
                'role' => AccessCatalog::ROLE_USER,
            ],
        ];

        foreach ($accounts as $account) {
            $role = $account['role'];
            unset($account['role']);

            $phone = PhoneNormalizer::toLocal($account['phone']);
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
