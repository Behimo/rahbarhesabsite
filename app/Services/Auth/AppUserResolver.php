<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;

class AppUserResolver
{
    public function optional(Request $request): ?User
    {
        $user = $request->user('sanctum');

        if (! $user instanceof User || $user->isBlocked() || ! $user->tokenCan(AppTokenIssuer::ABILITY)) {
            return null;
        }

        return $user;
    }
}
