<?php

use App\Services\ThemeService;

if (! function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        return app(ThemeService::class)->asset($path);
    }
}

if (! function_exists('fa_digits')) {
    function fa_digits(int|string|null $value): string
    {
        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
            ',' => '٬',
        ]);
    }
}
