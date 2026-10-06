<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AccessCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CmsEnsureAdminCommand extends Command
{
    protected $signature = 'cms:ensure-admin {--reset-password : رمز عبور ادمین را با مقدار CMS_ADMIN_PASSWORD بازنویسی می‌کند}';

    protected $description = 'ایجاد مدیر سیستم از .env (بدون بازنویسی خودکار رمز)';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) config('cms.admin_email', 'admin@rahbarhesab.ir')));
        $password = trim((string) config('cms.admin_password'));

        if ($email === '') {
            $this->error('CMS_ADMIN_EMAIL در .env تنظیم نشده است.');

            return self::FAILURE;
        }

        $admin = User::query()->where('email', $email)->first();
        $reset = (bool) $this->option('reset-password');

        if ($admin === null || $reset) {
            if ($password === '') {
                $this->error($admin === null
                    ? 'CMS_ADMIN_PASSWORD خالی است و کاربر ادمین وجود ندارد — ادمین ساخته نشد.'
                    : 'CMS_ADMIN_PASSWORD برای بازنویسی رمز خالی است.');

                return self::FAILURE;
            }

            if (Str::length($password) < 12) {
                $this->error('CMS_ADMIN_PASSWORD باید حداقل ۱۲ کاراکتر باشد.');

                return self::FAILURE;
            }
        }

        AccessCatalog::install();

        if ($admin === null) {
            $admin = User::query()->create([
                'email' => $email,
                'name' => 'مدیر سایت',
                'password' => $password,
                'status' => 'active',
            ]);
        } elseif ($reset) {
            $admin->forceFill(['password' => $password])->save();
            $this->info('رمز عبور ادمین بازنویسی شد.');
        }

        $admin->syncRoles([AccessCatalog::ROLE_ADMIN]);

        $this->info("مدیر سیستم: {$admin->email} (id: {$admin->id})");

        return self::SUCCESS;
    }
}
