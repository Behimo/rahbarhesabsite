<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PersianSlug
{
    private const MAP = [
        'ا' => 'a', 'آ' => 'a', 'ب' => 'b', 'پ' => 'p', 'ت' => 't', 'ث' => 's',
        'ج' => 'j', 'چ' => 'ch', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'z',
        'ر' => 'r', 'ز' => 'z', 'ژ' => 'zh', 'س' => 's', 'ش' => 'sh', 'ص' => 's',
        'ض' => 'z', 'ط' => 't', 'ظ' => 'z', 'ع' => 'a', 'غ' => 'gh', 'ف' => 'f',
        'ق' => 'gh', 'ک' => 'k', 'ك' => 'k', 'گ' => 'g', 'ل' => 'l', 'م' => 'm',
        'ن' => 'n', 'و' => 'v', 'ه' => 'h', 'ی' => 'y', 'ي' => 'y', 'ئ' => 'y',
        'ء' => '', 'ٌ' => '', 'ٍ' => '', 'ً' => '', 'ُ' => '', 'ِ' => '', 'َ' => '',
        'ّ' => '', 'ْ' => '', 'ٔ' => '', '‌' => '-', ' ' => '-',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    public static function make(string $value, string $fallbackPrefix = 'post'): string
    {
        $converted = strtr(trim($value), self::MAP);
        $slug = Str::slug($converted);

        if ($slug === '') {
            $slug = $fallbackPrefix.'-'.Str::lower(Str::random(6));
        }

        return $slug;
    }

    public static function unique(string $value, callable $exists, string $fallbackPrefix = 'post'): string
    {
        $base = self::make($value, $fallbackPrefix);
        $slug = $base;
        $i = 2;

        while ($exists($slug)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
