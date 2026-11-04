<?php

namespace App\Services;

use App\Jobs\SendOtpSmsJob;
use App\Models\OtpLog;
use App\Models\OtpVerification;
use App\Services\Sms\SmsManager;
use App\Support\OtpCode;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class OtpService
{
    public const PURPOSE_WEB = 'web_login';

    public const PURPOSE_APP = 'app_login';

    private const FAILURE_LIMIT = 10;

    public function __construct(private SmsManager $sms)
    {
    }

    public function send(string $phone, string $purpose = self::PURPOSE_WEB): string
    {
        $normalized = PhoneNormalizer::toE164($phone);
        $local = PhoneNormalizer::toLocal($phone);

        if (!PhoneNormalizer::isValidIranMobile($phone)) {
            throw new \InvalidArgumentException('شماره موبایل معتبر نیست.');
        }

        if (!in_array($purpose, [self::PURPOSE_WEB, self::PURPOSE_APP], true)) {
            throw new \InvalidArgumentException('هدف کد تأیید نامعتبر است.');
        }

        $ip = (string) Request::ip();
        $this->assertNotThrottled($normalized, $ip);
        $this->assertNotLocked($normalized);
        $length = $this->codeLength();
        $this->sms->ensureConfigured();

        $lock = Cache::lock('otp:send:' . $normalized, 15);
        if (!$lock->get()) {
            throw new OtpRequestException('لطفاً یک دقیقه صبر کنید و دوباره تلاش کنید.');
        }

        $cooldownKey = 'otp:cooldown:' . $normalized;
        $cooldownHeld = false;

        try {
            $cooldownSeconds = max(1, (int) config('otp.resend_cooldown_seconds', 60));
            if (!Cache::add($cooldownKey, true, $cooldownSeconds)) {
                throw new OtpRequestException('لطفاً یک دقیقه صبر کنید و دوباره تلاش کنید.');
            }
            $cooldownHeld = true;

            $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
            $challengeId = Str::random(40);

            OtpVerification::query()
                ->where('phone', $normalized)
                ->where('purpose', $purpose)
                ->whereNull('verified_at')
                ->delete();

            $verification = OtpVerification::query()->create([
                'phone' => $normalized,
                'purpose' => $purpose,
                'challenge_id' => $challengeId,
                'code' => OtpCode::seal($code),
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

            SendOtpSmsJob::dispatch($verification->id)->afterCommit();

            Cache::add('otp:throttle:phone:' . $normalized, 0, 120);
            Cache::increment('otp:throttle:phone:' . $normalized);
            Cache::add('otp:throttle:ip:' . $ip, 0, 120);
            Cache::increment('otp:throttle:ip:' . $ip);
            $cooldownHeld = false;

            return $challengeId;
        } catch (\Throwable $exception) {
            if ($cooldownHeld) {
                Cache::forget($cooldownKey);
            }

            throw $exception;
        } finally {
            $lock->release();
        }
    }

    public function verify(string $phone, string $code, string $purpose = self::PURPOSE_WEB, ?string $challengeId = null): bool
    {
        $normalized = PhoneNormalizer::toE164($phone);
        $local = PhoneNormalizer::toLocal($phone);
        $code = PhoneNormalizer::normalizeOtpCode($code) ?? '';
        $expectedLength = $this->codeLength();

        if (strlen($code) !== $expectedLength) {
            return false;
        }

        if ($this->isLocked($normalized)) {
            return false;
        }

        if ($purpose === self::PURPOSE_APP && ($challengeId === null || $challengeId === '')) {
            $this->recordFailure($normalized);

            return false;
        }

        return DB::transaction(function () use ($normalized, $local, $code, $purpose, $challengeId): bool {
            $otp = OtpVerification::query()
                ->where('phone', $normalized)
                ->where('purpose', $purpose)
                ->whereNull('verified_at')
                ->when(
                    $purpose === self::PURPOSE_APP,
                    fn($query) => $query->where('challenge_id', $challengeId),
                )
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (!$otp || $otp->isExpired() || $otp->attempts >= config('otp.max_attempts', 5)) {
                $this->recordFailure($normalized);

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

            if (!OtpCode::matches($code, (string) $otp->code)) {
                $this->recordFailure($normalized);

                return false;
            }

            $otp->update(['verified_at' => now()]);
            $log?->update(['is_used' => true]);
            Cache::forget($this->failureKey($normalized));

            return true;
        });
    }

    public function peekLatestCode(string $phone, string $purpose = self::PURPOSE_WEB): ?string
    {
        if (!app()->environment('local', 'testing')) {
            return null;
        }

        $stored = OtpVerification::query()
            ->where('phone', PhoneNormalizer::toE164($phone))
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->value('code');

        if (!is_string($stored) || $stored === '') {
            return null;
        }

        $plain = OtpCode::open($stored);

        return $plain === '' ? null : $plain;
    }

    private function codeLength(): int
    {
        $length = filter_var(config('otp.length', 6), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 4, 'max_range' => 8],
        ]);

        if ($length === false) {
            throw new \LogicException('OTP length must be between 4 and 8 digits.');
        }

        return $length;
    }

    private function assertNotThrottled(string $e164, string $ip): void
    {
        $phoneHits = (int) Cache::get('otp:throttle:phone:' . $e164, 0);
        $ipHits = (int) Cache::get('otp:throttle:ip:' . $ip, 0);

        if ($phoneHits >= 3 || $ipHits >= 8) {
            throw new OtpRequestException('تعداد درخواست‌ها بیش از حد مجاز است. چند دقیقه دیگر تلاش کنید.');
        }
    }

    private function assertNotLocked(string $e164): void
    {
        if ($this->isLocked($e164)) {
            throw new OtpRequestException('تعداد تلاش‌های ناموفق بیش از حد مجاز است. یک ساعت دیگر تلاش کنید.');
        }
    }

    private function isLocked(string $e164): bool
    {
        return (int) Cache::get($this->failureKey($e164), 0) >= self::FAILURE_LIMIT;
    }

    private function recordFailure(string $e164): void
    {
        $key = $this->failureKey($e164);

        if (!Cache::has($key)) {
            Cache::put($key, 0, now()->addHour());
        }

        Cache::increment($key);
    }

    private function failureKey(string $e164): string
    {
        return 'otp:failures:' . $e164;
    }
}
