<?php

namespace App\Console\Commands;

use App\Services\WordpressMigrationService;
use Illuminate\Console\Command;

class MigrateWpCoursesCommand extends Command
{
    protected $signature = 'wp:migrate-courses {--dry-run : فقط شمارش بدون نوشتن}';

    protected $description = 'مهاجرت محصولات/دوره‌های ووکامرس';

    public function handle(WordpressMigrationService $migration): int
    {
        if (! $migration->connectionReady()) {
            $this->error('اتصال WP_DB_DATABASE تنظیم یا در دسترس نیست.');

            return self::FAILURE;
        }

        $stats = $migration->migrateCourses((bool) $this->option('dry-run'));
        $this->info('دوره‌ها: '.json_encode($stats, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
