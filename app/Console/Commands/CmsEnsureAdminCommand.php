<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AccessCatalog;
use Illuminate\Console\Command;

class CmsEnsureAdminCommand extends Command
{
    protected $signature = 'cms:ensure-admin';

    protected $description = 'ایجاد یا به‌روزرسانی مدیر سیستم از .env';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) config('cms.admin_email', 'admin@rahbarhesab.ir')));
        $password = trim((string) config('cms.admin_password'));

        if ($password === '') {
            $password = 'password';
        }

        if ($email === '') {
            $this->error('CMS_ADMIN_EMAIL در .env تنظیم نشده است.');

            return self::FAILURE;
        }

        AccessCatalog::install();

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'مدیر سایت',
                'password' => $password,
                'status' => 'active',
            ]
        );

        $admin->syncRoles([AccessCatalog::ROLE_ADMIN]);

        $this->info("مدیر سیستم: {$admin->email} (id: {$admin->id})");

        return self::SUCCESS;
    }
}
