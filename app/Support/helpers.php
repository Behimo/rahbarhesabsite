<?php

use Illuminate\Support\Carbon;

if (! function_exists('clean_html')) {
    function clean_html(?string $html): string
    {
        $clean = \App\Support\HtmlSanitizer::clean($html);

        try {
            return app(\App\Services\ContentUrlRewriter::class)->rewrite($clean);
        } catch (\Throwable) {
            return $clean;
        }
    }
}

if (! function_exists('wp_shortcodes')) {
    /**
     * Strip or light-transform leftover WordPress shortcodes so they never render raw.
     */
    function wp_shortcodes(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        // [gallery ids="1,2"] → empty (images should already be in HTML after migration)
        $html = preg_replace('/\[gallery[^\]]*\]/iu', '', $html) ?? $html;
        $html = preg_replace('/\[caption[^\]]*\](.*?)\[\/caption\]/isu', '$1', $html) ?? $html;
        $html = preg_replace('/\[embed\](.*?)\[\/embed\]/isu', '<p><a href="$1">$1</a></p>', $html) ?? $html;
        $html = preg_replace('/\[video[^\]]*\](.*?)\[\/video\]/isu', '$1', $html) ?? $html;
        // Drop unknown shortcodes rather than showing brackets to users/Google
        $html = preg_replace('/\[\/?[a-z][a-z0-9_-]*[^\]]*\]/iu', '', $html) ?? $html;

        return $html;
    }
}

if (! function_exists('block_media')) {
    function block_media(?string $path, string $fallback = ''): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            $path = $fallback;
        }

        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset($path);
    }
}

if (! function_exists('admin_can')) {
    function admin_can(string $group, string $action): bool
    {
        return \App\Support\AccessCatalog::allows(
            auth()->user(),
            \App\Support\AccessCatalog::permission($group, $action)
        );
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

if (! function_exists('fa_ago')) {
    function fa_ago(DateTimeInterface|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $date = $value instanceof DateTimeInterface
            ? Carbon::parse($value)
            : Carbon::parse((string) $value);
        $seconds = max(0, now()->getTimestamp() - $date->getTimestamp());

        if ($seconds < 60) {
            return 'لحظاتی پیش';
        }

        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return fa_digits($minutes).' دقیقه پیش';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return fa_digits($hours).' ساعت پیش';
        }

        $days = intdiv($hours, 24);
        if ($days < 7) {
            return fa_digits($days).' روز پیش';
        }

        if ($days < 30) {
            return fa_digits(max(1, intdiv($days, 7))).' هفته پیش';
        }

        $months = max(1, intdiv($days, 30));
        if ($months < 12) {
            return fa_digits($months).' ماه پیش';
        }

        return fa_digits(max(1, intdiv($months, 12))).' سال پیش';
    }
}
