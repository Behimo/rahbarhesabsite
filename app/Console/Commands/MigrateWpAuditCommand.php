<?php

namespace App\Console\Commands;

use App\Services\WordpressMigrationService;
use Illuminate\Console\Command;

class MigrateWpAuditCommand extends Command
{
    protected $signature = 'wp:migrate-audit';

    protected $description = 'مقایسه تعداد رکوردهای وردپرس با دیتابیس فعلی';

    public function handle(WordpressMigrationService $migration): int
    {
        if (! $migration->connectionReady()) {
            $this->error('اتصال WP_DB_DATABASE تنظیم یا در دسترس نیست.');

            return self::FAILURE;
        }

        $stats = $migration->audit();
        foreach ($stats as $key => $value) {
            $this->line("{$key}: {$value}");
        }

        return self::SUCCESS;
    }
}
