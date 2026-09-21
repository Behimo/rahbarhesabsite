<?php

namespace App\Console\Commands;

use App\Models\OtpLog;
use Illuminate\Console\Command;

class PruneOtpLogsCommand extends Command
{
    protected $signature = 'otp:prune-logs {--months=12 : حذف لاگ‌های قدیمی‌تر از این تعداد ماه}';

    protected $description = 'حذف otp_logs قدیمی‌تر از ۱۲ ماه';

    public function handle(): int
    {
        $months = max(1, (int) $this->option('months'));
        $deleted = OtpLog::query()->where('sent_at', '<', now()->subMonths($months))->delete();
        $this->info("{$deleted} لاگ OTP حذف شد.");

        return self::SUCCESS;
    }
}
