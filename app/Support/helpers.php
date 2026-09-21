<?php

use App\Services\ThemeService;
use Illuminate\Support\Carbon;

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

if (! function_exists('fa_date')) {
    function fa_date(DateTimeInterface|string|null $value, string $pattern = 'yyyy/MM/dd'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $date = $value instanceof DateTimeInterface ? $value : new DateTimeImmutable((string) $value);

        if (class_exists(IntlDateFormatter::class)) {
            $formatter = new IntlDateFormatter(
                'fa_IR@calendar=persian',
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                'Asia/Tehran',
                IntlDateFormatter::TRADITIONAL,
                $pattern
            );

            $formatted = $formatter->format($date);

            if ($formatted !== false) {
                return $formatted;
            }
        }

        return fa_digits(Carbon::parse($date)->timezone('Asia/Tehran')->format('Y/m/d'));
    }
}
