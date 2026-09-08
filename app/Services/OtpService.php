<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Services\Sms\IpPanelSmsService;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    public function __construct(private IpPanelSmsService $sms) {}

    public function send(string $phone): void
    {
        $normalized = PhoneNormalizer::toE164($phone);

        if (! PhoneNormalizer::isValidIranMobile($phone)) {
            throw new \InvalidArgumentException('شماره موبایل معتبر نیست.');
        }

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

        if (! $this->sms->sendOtp($normalized, $code)) {
            throw new \RuntimeException('ارسال پیامک با خطا مواجه شد.');
        }

        Cache::put($cooldownKey, true, config('otp.resend_cooldown_seconds', 60));
    }

    public function verify(string $phone, string $code): bool
    {
        $normalized = PhoneNormalizer::toE164($phone);
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

        if (! hash_equals($otp->code, $code)) {
            return false;
        }

        $otp->update(['verified_at' => now()]);

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
}
