<?php

namespace App\Support;

class PhoneNormalizer
{
    public static function toLatinDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        return str_replace($persian, range(0, 9), str_replace($arabic, range(0, 9), trim($value)));
    }

    public static function normalizeOtpCode(?string $code): ?string
    {
        return self::toLatinDigits($code);
    }

    public static function toE164(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', self::toLatinDigits($phone) ?? '') ?? '';

        if (str_starts_with($phone, '98') && strlen($phone) === 12) {
            return '+'.$phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+98'.substr($phone, 1);
        }

        if (str_starts_with($phone, '9') && strlen($phone) === 10) {
            return '+98'.$phone;
        }

        return '+'.$phone;
    }

    public static function toLocal(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', self::toLatinDigits($phone) ?? '') ?? '';

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            return '0'.substr($digits, 2);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '0'.$digits;
        }

        return $digits;
    }

    public static function isValidIranMobile(string $phone): bool
    {
        $local = self::toLocal($phone);

        return (bool) preg_match('/^09\d{9}$/', $local);
    }
}
