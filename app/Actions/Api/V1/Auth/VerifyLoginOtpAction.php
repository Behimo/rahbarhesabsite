<?php

namespace App\Actions\Api\V1\Auth;

use App\Exceptions\Api\ApiException;
use App\Models\User;
use App\Services\Auth\AppTokenIssuer;
use App\Services\OtpService;
use App\Support\AuditLog;
use App\Support\PhoneNormalizer;
use Laravel\Sanctum\NewAccessToken;

class VerifyLoginOtpAction
{
    public function __construct(
        private OtpService $otp,
        private AppTokenIssuer $tokens,
    ) {}

    public function handle(string $phone, string $code, string $challengeId, string $deviceName, ?string $ip): NewAccessToken
    {
        if (! $this->otp->verify($phone, $code, OtpService::PURPOSE_APP, $challengeId)) {
            throw new ApiException('کد تأیید نامعتبر یا منقضی شده است.', 'otp_invalid', 401);
        }

        $existing = User::findByPhone($phone);
        if ($existing && ($existing->needsActivation() || $existing->isBlocked())) {
            AuditLog::record('app.login.rejected', [
                'user_id' => $existing->id,
                'ip' => $ip,
                'reason' => $existing->status,
            ]);

            throw new ApiException('حساب کاربری شما غیرفعال است.', 'account_inactive', 403);
        }

        $user = $existing ?? User::findOrCreateByPhone($phone);
        $local = PhoneNormalizer::toLocal($phone);
        $user->forceFill([
            'mobile' => $local,
            'phone' => $local,
            'mobile_verified_at' => $user->mobile_verified_at ?? now(),
            'last_login_at' => now(),
        ])->save();

        $issued = $this->tokens->issue($user->fresh(), $deviceName);

        AuditLog::record('app.token.issued', [
            'user_id' => $user->id,
            'ip' => $ip,
            'device' => mb_substr($deviceName, 0, 80),
        ]);

        return $issued;
    }
}
