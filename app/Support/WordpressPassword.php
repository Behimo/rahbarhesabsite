<?php

namespace App\Support;

class WordpressPassword
{
    private const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public static function isWordpressHash(string $hash): bool
    {
        return str_starts_with($hash, '$P$') || str_starts_with($hash, '$H$');
    }

    public static function check(string $password, string $storedHash): bool
    {
        if (! self::isWordpressHash($storedHash) || strlen($storedHash) !== 34) {
            return false;
        }

        $countLog2 = strpos(self::ITOA64, $storedHash[3]);
        if ($countLog2 < 7 || $countLog2 > 30) {
            return false;
        }

        $count = 1 << $countLog2;
        $salt = substr($storedHash, 4, 8);
        $hash = md5($salt.$password, true);

        do {
            $hash = md5($hash.$password, true);
        } while (--$count);

        $encoded = $storedHash[0].$storedHash[1].$storedHash[2].$storedHash[3].$salt.self::encode64($hash, 16);

        return hash_equals($storedHash, $encoded);
    }

    private static function encode64(string $input, int $count): string
    {
        $output = '';
        $i = 0;

        do {
            $value = ord($input[$i++]);
            $output .= self::ITOA64[$value & 0x3f];

            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }

            $output .= self::ITOA64[($value >> 6) & 0x3f];

            if ($i++ >= $count) {
                break;
            }

            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }

            $output .= self::ITOA64[($value >> 12) & 0x3f];

            if ($i++ >= $count) {
                break;
            }

            $output .= self::ITOA64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }
}
