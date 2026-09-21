<?php

namespace App\Jobs;

use App\Services\Sms\IpPanelSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        if (! $sms->sendOtp($this->phone, $this->code)) {
            throw new \RuntimeException('ارسال پیامک OTP ناموفق بود.');
        }
    }
}
