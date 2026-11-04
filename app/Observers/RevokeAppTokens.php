<?php

namespace App\Observers;

use App\Models\User;

class RevokeAppTokens
{
    public function updated(User $user): void
    {
        if ($user->wasChanged(['status', 'mobile', 'phone', 'password'])) {
            $this->revoke($user);
        }
    }

    public function deleted(User $user): void
    {
        $this->revoke($user);
    }

    private function revoke(User $user): void
    {
        $user->tokens()->delete();
    }
}
