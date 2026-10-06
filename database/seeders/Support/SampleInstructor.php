<?php

namespace Database\Seeders\Support;

use App\Models\User;
use App\Support\AccessCatalog;

final class SampleInstructor
{
    public const EMAIL = 'instructor@rahbarhesab.com';

    public const PHONE = '09120000001';

    /**
     * در production کاربر نمونه با رمز ثابت ساخته نمی‌شود و رمز موجود عوض نمی‌شود.
     */
    public static function findOrCreate(): ?User
    {
        if (app()->environment('production')) {
            $existing = self::existing();

            if ($existing) {
                $existing->syncRoles([AccessCatalog::ROLE_INSTRUCTOR]);
            }

            return $existing;
        }

        $user = User::query()->updateOrCreate(
            ['phone' => self::PHONE],
            [
                'name' => 'مرتضی رهبر',
                'email' => self::EMAIL,
                'mobile' => self::PHONE,
                'password' => 'password',
                'status' => 'active',
            ]
        );
        $user->syncRoles([AccessCatalog::ROLE_INSTRUCTOR]);

        return $user;
    }

    private static function existing(): ?User
    {
        return User::query()
            ->where('email', self::EMAIL)
            ->orWhere('phone', self::PHONE)
            ->orWhere('mobile', self::PHONE)
            ->first();
    }
}
