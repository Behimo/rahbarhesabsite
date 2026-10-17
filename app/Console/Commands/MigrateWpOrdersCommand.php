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

        $total = $migration->countPaidOrders();
        $this->info(($this->option('dry-run') ? '[dry-run] ' : '')."مهاجرت سفارش‌ها ({$total} سفارش)...");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% | ETA %remaining:6s%');
        $bar->start();

        $stats = $migration->migrateOrders((bool) $this->option('dry-run'), function () use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('سفارش‌ها: '.json_encode($stats, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
