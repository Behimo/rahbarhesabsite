<?php

namespace App\Services\Wordpress;

class WordpressProductValues
{
    public function regularAndSalePrice(array $meta): array
    {
        $regular = (int) ($meta['_regular_price'] ?? 0);
        $current = (int) ($meta['_price'] ?? 0);
        $saleRaw = $meta['_sale_price'] ?? null;
        $sale = ($saleRaw !== null && $saleRaw !== '') ? (int) $saleRaw : null;

        if ($sale !== null && $regular > $sale) {
            return [$regular, $sale];
        }

        if ($sale !== null && $current > $sale) {
            return [$current, $sale];
        }

        return [$current > 0 ? $current : $regular, null];
    }

    public function minutesFromDurationLabel(?string $label): ?int
    {
        $label = trim((string) $label);
        if ($label === '') {
            return null;
        }

        $label = strtr($label, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $hours = 0;
        $minutes = 0;
        if (preg_match('/(\d+)\s*ساعت/u', $label, $match)) {
            $hours = (int) $match[1];
        }
        if (preg_match('/(\d+)\s*دقیقه/u', $label, $match)) {
            $minutes = (int) $match[1];
        } elseif (preg_match('/ساعت\s*و\s*(\d+)/u', $label, $match)) {
            $minutes = (int) $match[1];
        }

        $total = ($hours * 60) + $minutes;

        return $total > 0 ? $total : null;
    }

    public function normalizeSpotplayerCourseId(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $first = collect(explode(',', (string) $raw))
            ->map(fn (string $id) => trim($id))
            ->filter()
            ->first();

        if (! is_string($first) || $first === '') {
            return null;
        }

        return mb_substr($first, 0, 100);
    }
}
