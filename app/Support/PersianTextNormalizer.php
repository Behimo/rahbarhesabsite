<?php

namespace App\Support;

class PersianTextNormalizer
{
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        $map = [
            'ي' => 'ی',
            'ك' => 'ک',
            'ة' => 'ه',
            'ؤ' => 'و',
            'إ' => 'ا',
            'أ' => 'ا',
            'آ' => 'ا',
            'ۀ' => 'ه',
            'ء' => '',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ];

        $text = strtr($text, $map);

        // Remove Arabic diacritics and tatweel.
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text) ?? $text;

        // Collapse ZWNJ / ZWSP / NBSP into space.
        $text = preg_replace('/[\x{200C}\x{200B}\x{00A0}]+/u', ' ', $text) ?? $text;

        // Strip separators used to evade filters inside words.
        $text = preg_replace('/(?<=\p{L})[.\-_*=+|\/\\\\]+(?=\p{L})/u', '', $text) ?? $text;

        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    public static function containsWord(string $haystack, string $needle): bool
    {
        $haystack = self::normalize($haystack);
        $needle = self::normalize($needle);

        if ($needle === '') {
            return false;
        }

        return preg_match('/(?:^|[^\p{L}\p{N}])'.preg_quote($needle, '/').'(?:$|[^\p{L}\p{N}])/u', $haystack) === 1
            || str_contains($haystack, $needle);
    }
}
