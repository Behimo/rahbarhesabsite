<?php

namespace App\Console\Commands;

use App\Services\WordpressMigrationService;
use Illuminate\Console\Command;

class MigrateWpUsersCommand extends Command
{
    protected $signature = 'wp:migrate-users {--dry-run : فقط شمارش بدون نوشتن}';

    protected $description = 'مهاجرت کاربران وردپرس به users';

    public function handle(WordpressMigrationService $migration): int
    {
        if (! $migration->connectionReady()) {
            $this->error('اتصال WP_DB_DATABASE تنظیم یا در دسترس نیست.');

            return self::FAILURE;
        }

        $stats = $migration->migrateUsers((bool) $this->option('dry-run'));
        $this->info('کاربران: '.json_encode($stats, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
