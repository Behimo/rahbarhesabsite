<?php

namespace App\Console\Commands;

use App\Services\WordpressMigrationService;
use Illuminate\Console\Command;

class MigrateWpPagesCommand extends Command
{
    protected $signature = 'wp:migrate-pages
                            {--dry-run : فقط شمارش بدون نوشتن}
                            {--source=rendered : منبع محتوا: rendered (HTML زنده Elementor) یا content (post_content)}
                            {--include-system : برگه‌های سیستمی (about/cart/…) را هم منتقل کن}
                            {--download-css : دانلود CSSهای Elementor به public/migrated-wp}';

    protected $description = 'مهاجرت برگه‌های ثابت وردپرس (HTML + استایل Elementor)';

    public function handle(WordpressMigrationService $migration): int
    {
        if (! $migration->connectionReady()) {
            $this->error('اتصال WP_DB_DATABASE تنظیم یا در دسترس نیست.');

            return self::FAILURE;
        }

        $source = strtolower((string) $this->option('source'));
        if (! in_array($source, ['rendered', 'content'], true)) {
            $this->error('مقدار --source باید rendered یا content باشد.');

            return self::FAILURE;
        }

        $total = $migration->countPages();
        $this->info(($this->option('dry-run') ? '[dry-run] ' : '')."مهاجرت برگه‌ها ({$total} صفحه، source={$source})...");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% | ETA %remaining:6s%');
        $bar->start();

        $stats = $migration->migratePages(
            dryRun: (bool) $this->option('dry-run'),
            onProgress: function () use ($bar) {
                $bar->advance();
            },
            options: [
                'source' => $source,
                'include_system' => (bool) $this->option('include-system'),
                'download_css' => (bool) $this->option('download-css'),
            ],
        );

        $bar->finish();
        $this->newLine(2);
        $this->info('برگه‌ها: '.json_encode($stats, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
