<?php

namespace App\Support;

class RobotsDirectives
{
    public const OPTIONS = ['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'];

    public static function normalize(?string $value): string
    {
        $parts = preg_split('/,\s*(?=[a-z][a-z-]*(?:\s*:|,|$))/i', strtolower(trim($value ?? '', " \t\n\r\0\x0B,")));
        $parts = array_map(fn ($part) => preg_replace('/\s*:\s*/', ':', trim($part)), $parts);
        $parts = array_merge(...array_map(fn ($part) => match ($part) {
            'none' => ['noindex', 'nofollow'],
            'all' => ['index', 'follow'],
            default => [$part],
        }, $parts));

        return implode(', ', array_values(array_unique(array_filter($parts))));
    }

    public static function valid(string $value): bool
    {
        foreach (explode(', ', self::normalize($value)) as $directive) {
            if (in_array($directive, ['index', 'noindex', 'follow', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex', 'notranslate', 'indexifembedded', 'nocache'], true)) {
                continue;
            }
            if (preg_match('/^(?:max-snippet|max-video-preview):(?:-1|\d+)$/', $directive)
                || preg_match('/^max-image-preview:(?:none|standard|large)$/', $directive)) {
                continue;
            }

            return false;
        }

        return true;
    }

    public static function restrictive(string $policy, ?string $content): string
    {
        $policyParts = explode(', ', self::normalize($policy));
        $contentParts = explode(', ', self::normalize($content));
        $parts = array_unique([...$policyParts, ...$contentParts]);
        $index = in_array('noindex', $parts, true) ? 'noindex' : 'index';
        $follow = in_array('nofollow', $parts, true) ? 'nofollow' : 'follow';
        // Snippet/preview hints of the default policy only make sense on indexable pages.
        $extras = array_diff($index === 'noindex' ? $contentParts : $parts, ['index', 'noindex', 'follow', 'nofollow', '']);

        return implode(', ', [$index, $follow, ...$extras]);
    }
}
