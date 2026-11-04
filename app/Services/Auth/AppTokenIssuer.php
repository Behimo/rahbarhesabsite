<?php

namespace App\Services\Auth;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class AppTokenIssuer
{
    public const ABILITY = 'app';

    public function issue(User $user, string $deviceName): NewAccessToken
    {
        $max = max(1, (int) config('cms.app.max_tokens', 5));
        $existing = $user->tokens()->orderBy('id')->get();
        $overflow = $existing->count() - ($max - 1);

        if ($overflow > 0) {
            $user->tokens()
                ->whereIn('id', $existing->take($overflow)->modelKeys())
                ->delete();
        }

        $days = max(1, (int) config('cms.app.token_ttl_days', 30));

        return $user->createToken(
            mb_substr($deviceName, 0, 255),
            [self::ABILITY],
            now()->addDays($days),
        );
    }
}
