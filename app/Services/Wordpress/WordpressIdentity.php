<?php

namespace App\Services\Wordpress;

use App\Support\PhoneNormalizer;

class WordpressIdentity
{
    public function resolveMobile(array $meta, string $fallback): ?string
    {
        foreach (['loginx_phone', 'digits_phone', 'billing_phone', 'phone'] as $key) {
            if (! empty($meta[$key]) && PhoneNormalizer::isValidIranMobile((string) $meta[$key])) {
                return PhoneNormalizer::toLocal((string) $meta[$key]);
            }
        }

        if (PhoneNormalizer::isValidIranMobile($fallback)) {
            return PhoneNormalizer::toLocal($fallback);
        }

        return null;
    }

    public function claimUniqueEmail(?string $email, int $wpId, array &$usedEmails): ?string
    {
        $email = $email !== null ? trim($email) : null;
        if ($email === null || $email === '') {
            return null;
        }

        $email = mb_substr($email, 0, 255);
        $key = mb_strtolower($email);
        $owner = $usedEmails[$key] ?? null;

        if ($owner !== null && $owner !== $wpId) {
            return null;
        }

        $usedEmails[$key] = $wpId;

        return $email;
    }

    public function mobileWinners(array $candidates): array
    {
        $byMobile = [];
        foreach ($candidates as $candidate) {
            $mobile = $candidate['mobile'];
            if ($mobile === null) {
                continue;
            }
            $byMobile[$mobile][] = $candidate;
        }

        $winners = [];
        foreach ($byMobile as $mobile => $group) {
            usort($group, function (array $a, array $b): int {
                if ($a['orders'] !== $b['orders']) {
                    return $b['orders'] <=> $a['orders'];
                }

                return (int) $a['row']->ID <=> (int) $b['row']->ID;
            });
            $winners[$mobile] = (int) $group[0]['row']->ID;
        }

        return $winners;
    }

    public function rawClaimedMobile(array $meta, string $fallback): ?string
    {
        foreach (['loginx_phone', 'digits_phone', 'billing_phone', 'phone'] as $key) {
            $raw = trim((string) ($meta[$key] ?? ''));
            if ($raw !== '') {
                return mb_substr($raw, 0, 15);
            }
        }

        $fallback = trim($fallback);
        if ($fallback !== '' && preg_match('/\d{8,}/', $fallback)) {
            return mb_substr($fallback, 0, 15);
        }

        return null;
    }
}
