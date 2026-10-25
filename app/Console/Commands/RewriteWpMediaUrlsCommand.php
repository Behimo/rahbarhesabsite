<?php

namespace App\Console\Commands;

use App\Services\WordpressMigrationService;
use Illuminate\Console\Command;

class RewriteWpMediaUrlsCommand extends Command
{
    protected $signature = 'wp:rewrite-media-urls';

    protected $description = 'تبدیل URL مطلق رسانه‌های وردپرس ذخیره‌شده به مسیر نسبی /wp-content/...';

    public function handle(WordpressMigrationService $migration): int
    {
        $stats = $migration->rewriteStoredMediaUrls();
        $this->info('بازنویسی URL رسانه: '.json_encode($stats, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
