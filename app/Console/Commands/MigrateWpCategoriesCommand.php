<?php

namespace App\Console\Commands;

use App\Services\WordpressMigrationService;
use Illuminate\Console\Command;

class MigrateWpCategoriesCommand extends Command
{
    protected $signature = 'wp:migrate-categories {--dry-run : فقط شمارش بدون نوشتن}';

    protected $description = 'مهاجرت دسته‌بندی‌های محصول و بلاگ وردپرس';

    public function handle(WordpressMigrationService $migration): int
    {
        if (! $migration->connectionReady()) {
            $this->error('اتصال WP_DB_DATABASE تنظیم یا در دسترس نیست.');

            return self::FAILURE;
        }

        $total = $migration->countCategories();
        $this->info(($this->option('dry-run') ? '[dry-run] ' : '')."مهاجرت دسته‌ها ({$total} ترم)...");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% | ETA %remaining:6s%');
        $bar->start();

        $stats = $migration->migrateCategories((bool) $this->option('dry-run'), function () use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('دسته‌ها: '.json_encode($stats, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
