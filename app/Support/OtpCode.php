<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class OtpCode
{
    public static function seal(string $code): string
    {
        return 'enc:'.Crypt::encryptString($code);
    }

    public static function open(string $stored): string
    {
        if (! str_starts_with($stored, 'enc:')) {
            return $stored;
        }

        try {
            return Crypt::decryptString(substr($stored, 4));
        } catch (DecryptException) {
            return '';
        }
    }

    public static function matches(string $plain, string $stored): bool
    {
        $opened = self::open($stored);

        return $opened !== '' && strlen($opened) === strlen($plain) && hash_equals($opened, $plain);
    }
}
