<?php

namespace App\Console\Commands;

use App\Services\WordpressMigrationService;
use Illuminate\Console\Command;

class MigrateWpTermLinksCommand extends Command
{
    protected $signature = 'wp:migrate-term-links {--dry-run : فقط شمارش بدون نوشتن}';

    protected $description = 'بازسازی اتصال دوره‌ها/مقالات به دسته‌ها و تگ‌های وردپرس';

    public function handle(WordpressMigrationService $migration): int
    {
        if (! $migration->connectionReady()) {
            $this->error('اتصال WP_DB_DATABASE تنظیم یا در دسترس نیست.');

            return self::FAILURE;
        }

        $this->info(($this->option('dry-run') ? '[dry-run] ' : '').'مهاجرت تگ‌ها...');
        $tagStats = $migration->migrateTags((bool) $this->option('dry-run'));
        $this->info('تگ‌ها: '.json_encode($tagStats, JSON_UNESCAPED_UNICODE));

        $total = $migration->countTermLinkTargets();
        $this->info(($this->option('dry-run') ? '[dry-run] ' : '')."اتصال ترم‌ها به محتوا ({$total} آیتم)...");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% | ETA %remaining:6s%');
        $bar->start();

        $stats = $migration->migrateTermLinks((bool) $this->option('dry-run'), function () use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('روابط: '.json_encode($stats, JSON_UNESCAPED_UNICODE));
        $this->comment('نکته: تگ‌های وردپرس بیشتر روی نوع post_type=qa (کافه سوال) هستند؛ مقالات بلاگ معمولاً فقط category دارند.');

        return self::SUCCESS;
    }
}
