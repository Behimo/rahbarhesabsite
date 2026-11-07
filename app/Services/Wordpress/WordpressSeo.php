<?php

namespace App\Services\Wordpress;

use Illuminate\Database\Eloquent\Model;

class WordpressSeo
{
    public function applyRankMathMeta(Model $model, array $meta, ?string $wpExcerpt, callable $resolveTemplate, callable $metaString, callable $robotsForMeta, callable $normalizeMedia): void
    {
        // Rank Math's default description is %excerpt%: the whole manual excerpt (WooCommerce short
        // description) as plain text when present; otherwise the content excerpt built at render time.
        $excerpt = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(
            (string) preg_replace('/<\/(p|li|div|h[1-6])>|<br\s*\/?>/iu', ' ', (string) $wpExcerpt)
        ), ENT_QUOTES | ENT_HTML5)));
        $excerpt = $excerpt !== '' ? $excerpt : null;
        $title = $resolveTemplate(
            $metaString($meta, ['rank_math_title', 'rank_math_facebook_title']),
            $model,
        );
        $description = $resolveTemplate(
            $metaString($meta, ['rank_math_description', 'rank_math_facebook_description']),
            $model,
            $excerpt,
        ) ?? $excerpt;
        $robots = $robotsForMeta($meta);
        $canonical = $metaString($meta, ['rank_math_canonical_url']);
        $ogImage = $metaString($meta, ['rank_math_facebook_image', 'rank_math_twitter_image']);

        if ($title) {
            $model->meta_title = mb_substr($title, 0, 191);
        }
        if ($description) {
            $model->meta_description = mb_substr($description, 0, 500);
        }
        if ($robots && $model->isFillable('robots')) {
            $model->robots = $robots;
        }
        if ($canonical && $model->isFillable('canonical_url')) {
            $model->canonical_url = mb_substr($canonical, 0, 500);
        }
        if ($ogImage && $model->isFillable('og_image')) {
            $model->og_image = $normalizeMedia($ogImage);
        }
    }

    public function resolveRankMathTemplate(?string $template, Model $model, ?string $excerpt = null): ?string
    {
        if ($template === null || $template === '' || ! str_contains($template, '%')) {
            return $template;
        }

        $values = [
            'title' => trim((string) ($model->title ?? $model->name ?? '')),
            'sitename' => (string) config('cms.site_name_fa'),
            'sep' => '-',
            'excerpt' => (string) $excerpt,
            'excerpt_only' => (string) $excerpt,
        ];
        // Rank Math matches %…% greedily and case-sensitively, drops unknown variables
        // (e.g. "%SITename%", "%sitename%%") and collapses doubled separators.
        $resolved = preg_replace_callback(
            '/%(([a-z0-9_-]+)\(([^)]*)\)|[^\s]+)%/iu',
            fn (array $match) => $values[$match[1]] ?? '',
            $template
        );
        $resolved = preg_replace('/\s+/u', ' ', (string) $resolved);
        $resolved = trim((string) preg_replace('/ - (?:- )+/u', ' - ', $resolved));

        return $resolved !== '' ? $resolved : null;
    }

    public function metaString(array $meta, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($meta[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    public function rankMathRobots(array $meta): ?string
    {
        $raw = $meta['rank_math_robots'] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }

        $parts = is_array($raw) ? $raw : (array) @unserialize((string) $raw);
        if (! is_array($parts) || $parts === []) {
            $parts = array_filter(array_map('trim', explode(',', (string) $raw)));
        }

        $parts = array_values(array_filter(array_map(fn ($p) => mb_strtolower(trim((string) $p)), $parts)));

        return $parts === [] ? null : implode(', ', $parts);
    }
}
