<?php

namespace App\Jobs;

use App\Models\OtpVerification;
use App\Services\Sms\SmsDeliveryException;
use App\Services\Sms\SmsManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOtpSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $verificationId) {}

    public function handle(SmsManager $sms): void
    {
        $verification = OtpVerification::query()->find($this->verificationId);

        if (! $verification || $verification->isVerified() || $verification->isExpired()) {
            return;
        }

        $latestId = OtpVerification::query()
            ->where('phone', $verification->phone)
            ->latest('id')
            ->value('id');

        if ((int) $latestId !== $verification->id) {
            return;
        }

        $context = [
            'job' => self::class,
            'verification_id' => $this->verificationId,
            'phone' => $this->maskedPhone($verification->phone),
            'attempt' => $this->job ? $this->attempts() : null,
        ];

        Log::channel('jobs')->info('SendOtpSmsJob: شروع ارسال OTP', $context);

        try {
            $sms->sendOtp($verification->phone, $verification->code);
        } catch (Throwable $exception) {
            Log::channel('jobs')->warning('SendOtpSmsJob: ارسال ناموفق؛ تلاش مجدد', $context + [
                'exception' => $exception::class,
            ] + ($exception instanceof SmsDeliveryException ? $exception->safeContext() : []));

            throw $exception;
        }

        Log::channel('jobs')->info('SendOtpSmsJob: درخواست ارسال پذیرفته شد', $context);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('jobs')->error('SendOtpSmsJob: شکست نهایی پس از تمام تلاش‌ها', [
            'job' => self::class,
            'verification_id' => $this->verificationId,
            'exception' => $exception ? $exception::class : null,
        ] + ($exception instanceof SmsDeliveryException ? $exception->safeContext() : []));
    }

    private function maskedPhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: $phone;

        if (strlen($phone) < 7) {
            return '***';
        }

        return substr($phone, 0, 4).str_repeat('*', max(strlen($phone) - 6, 3)).substr($phone, -2);
    }
}
