<?php

namespace App\Console\Commands;

use App\Services\WordpressMigrationService;
use Illuminate\Console\Command;

class MigrateWpOrdersCommand extends Command
{
    protected $signature = 'wp:migrate-orders {--dry-run : فقط شمارش بدون نوشتن}';

    protected $description = 'مهاجرت سفارشات ووکامرس و صدور دسترسی دوره‌ها';

    public function handle(WordpressMigrationService $migration): int
    {
        if (! $migration->connectionReady()) {
            $this->error('اتصال WP_DB_DATABASE تنظیم یا در دسترس نیست.');

            return self::FAILURE;
        }

        $stats = $migration->migrateOrders((bool) $this->option('dry-run'));
        $this->info('سفارش‌ها: '.json_encode($stats, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
