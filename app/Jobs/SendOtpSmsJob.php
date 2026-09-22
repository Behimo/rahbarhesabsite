<?php

namespace App\Jobs;

use App\Services\Sms\IpPanelSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOtpSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $phone,
        public string $code,
    ) {}

    public function handle(IpPanelSmsService $sms): void
    {
        $context = [
            'job' => self::class,
            'phone' => $this->maskedPhone(),
            'attempt' => $this->job ? $this->attempts() : null,
        ];

        Log::channel('jobs')->info('SendOtpSmsJob: شروع ارسال OTP', $context);

        if (! $sms->sendOtp($this->phone, $this->code)) {
            Log::channel('jobs')->warning('SendOtpSmsJob: ارسال ناموفق؛ تلاش مجدد', $context);

            throw new \RuntimeException('ارسال پیامک OTP ناموفق بود. phone='.$this->maskedPhone());
        }

        Log::channel('jobs')->info('SendOtpSmsJob: موفق', $context);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('jobs')->error('SendOtpSmsJob: شکست نهایی پس از تمام تلاش‌ها', [
            'job' => self::class,
            'phone' => $this->maskedPhone(),
            'error' => $exception?->getMessage(),
            'exception' => $exception ? $exception::class : null,
        ]);
    }

    private function maskedPhone(): string
    {
        $phone = preg_replace('/\D+/', '', $this->phone) ?: $this->phone;

        if (strlen($phone) < 7) {
            return '***';
        }

        return substr($phone, 0, 4).str_repeat('*', max(strlen($phone) - 6, 3)).substr($phone, -2);
    }
}
