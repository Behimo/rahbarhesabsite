<?php

namespace App\Services;

use App\Jobs\SendOtpSmsJob;
use App\Models\OtpLog;
use App\Models\OtpVerification;
use App\Services\Sms\IpPanelSmsService;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

class OtpService
{
    public function __construct(private IpPanelSmsService $sms) {}

    public function send(string $phone): void
    {
        $normalized = PhoneNormalizer::toE164($phone);
        $local = PhoneNormalizer::toLocal($phone);

        if (! PhoneNormalizer::isValidIranMobile($phone)) {
            throw new \InvalidArgumentException('شماره موبایل معتبر نیست.');
        }

        $ip = (string) Request::ip();
        $this->assertNotThrottled($normalized, $ip);

        $cooldownKey = 'otp:cooldown:'.$normalized;
        if (Cache::has($cooldownKey)) {
            throw new \RuntimeException('لطفاً یک دقیقه صبر کنید و دوباره تلاش کنید.');
        }

        $code = str_pad((string) random_int(0, 999999), config('otp.length', 6), '0', STR_PAD_LEFT);

        OtpVerification::query()
            ->where('phone', $normalized)
            ->whereNull('verified_at')
            ->delete();

        OtpVerification::query()->create([
            'phone' => $normalized,
            'code' => $code,
            'expires_at' => now()->addMinutes(config('otp.expires_minutes', 5)),
        ]);

        OtpLog::query()->create([
            'mobile' => $local,
            'ip_address' => $ip,
            'user_agent' => Request::userAgent(),
            'type' => 'login',
            'sent_at' => now(),
            'is_used' => false,
            'attempts' => 0,
        ]);

        SendOtpSmsJob::dispatch($normalized, $code);

        Cache::put($cooldownKey, true, config('otp.resend_cooldown_seconds', 60));
        Cache::add('otp:throttle:phone:'.$normalized, 0, 120);
        Cache::increment('otp:throttle:phone:'.$normalized);
        Cache::add('otp:throttle:ip:'.$ip, 0, 120);
        Cache::increment('otp:throttle:ip:'.$ip);
    }

    public function verify(string $phone, string $code): bool
    {
        $normalized = PhoneNormalizer::toE164($phone);
        $local = PhoneNormalizer::toLocal($phone);
        $code = PhoneNormalizer::normalizeOtpCode($code) ?? '';

        $otp = OtpVerification::query()
            ->where('phone', $normalized)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp || $otp->isExpired()) {
            return false;
        }

        if ($otp->attempts >= config('otp.max_attempts', 5)) {
            return false;
        }

        $otp->increment('attempts');

        $log = OtpLog::query()
            ->where('mobile', $local)
            ->where('is_used', false)
            ->latest('sent_at')
            ->first();

        if ($log) {
            $log->increment('attempts');
        }

        if (! hash_equals($otp->code, $code)) {
            return false;
        }

        $otp->update(['verified_at' => now()]);
        $log?->update(['is_used' => true]);

        return true;
    }

    public function peekLatestCode(string $phone): ?string
    {
        if (! app()->environment('local', 'testing')) {
            return null;
        }

        $normalized = PhoneNormalizer::toE164($phone);

        return OtpVerification::query()
            ->where('phone', $normalized)
            ->whereNull('verified_at')
            ->latest()
            ->value('code');
    }

    private function assertNotThrottled(string $e164, string $ip): void
    {
        $phoneHits = (int) Cache::get('otp:throttle:phone:'.$e164, 0);
        $ipHits = (int) Cache::get('otp:throttle:ip:'.$ip, 0);

        if ($phoneHits >= 3 || $ipHits >= 8) {
            throw new \RuntimeException('تعداد درخواست‌ها بیش از حد مجاز است. چند دقیقه دیگر تلاش کنید.');
        }
    }
}
