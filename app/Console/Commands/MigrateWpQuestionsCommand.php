<?php

namespace App\Console\Commands;

use App\Services\WordpressMigrationService;
use Illuminate\Console\Command;

class MigrateWpQuestionsCommand extends Command
{
    protected $signature = 'wp:migrate-questions {--dry-run : فقط شمارش بدون نوشتن} {--fallback-user= : شناسه‌ی کاربر Laravel برای نویسنده‌ی ناموجود}';

    protected $description = 'انتقال پرسش‌ها، پاسخ‌های رسمی و دیدگاه‌های وردپرس از بکاپ';

    public function handle(WordpressMigrationService $migration): int
    {
        if (! $migration->connectionReady()) {
            $this->error('اتصال WP_DB_DATABASE تنظیم یا در دسترس نیست.');

            return self::FAILURE;
        }
        $fallback = $this->option('fallback-user');
        if ($fallback !== null && (! ctype_digit((string) $fallback) || (int) $fallback < 1)) {
            $this->error('--fallback-user باید شناسه‌ی مثبت کاربر Laravel باشد.');

            return self::FAILURE;
        }
        $total = $migration->countQuestions();
        $this->info(($this->option('dry-run') ? '[dry-run] ' : '')."مهاجرت پرسش‌ها ({$total} پرسش)...");
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% | ETA %remaining:6s%');
        $bar->start();
        try {
            $stats = $migration->migrateQuestions((bool) $this->option('dry-run'),
                $fallback !== null ? (int) $fallback : null, fn () => $bar->advance());
        } catch (\Throwable $exception) {
            $this->newLine(2);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $bar->finish();
        $this->newLine(2);
        $this->info('پرسش‌ها: '.json_encode($stats, JSON_UNESCAPED_UNICODE));
        foreach ($stats['conflicts'] as $conflict) {
            $this->warn($conflict);
        }

        return $stats['conflicts'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
