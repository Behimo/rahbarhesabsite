<?php

namespace App\Support;

class HtmlSanitizer
{
    public static function clean(?string $html): string
    {
        $html = self::stripDangerous($html);

        if ($html === '') {
            return '';
        }

        // Remove <style> before strip_tags so CSS text is not left behind.
        $html = self::stripInlineStyles($html);
        $allowed = '<p><br><strong><em><b><i><u><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6><blockquote><table><thead><tbody><tr><th><td><figure><figcaption><span><div><hr><pre><code>';

        return strip_tags($html, $allowed);
    }

    /**
     * Lighter pass for trusted/admin-imported HTML (e.g. WordPress bodies).
     * Keeps <style> tags so PageCssIsolator can scope them at render time.
     */
    public static function cleanImported(?string $html): string
    {
        $html = self::stripDangerous($html);

        if ($html === '') {
            return '';
        }

        // Keep inline style= for Elementor-like layouts; drop only dangerous URLs.
        return preg_replace(
            '/\s(style|background)\s*=\s*(["\'])\s*(?:[^"\']*(?:javascript:|expression\s*\(|data:))[^"\']*\2/iu',
            '',
            $html
        ) ?? $html;
    }

    private static function stripDangerous(?string $html): string
    {
        $html = (string) $html;

        if (trim($html) === '') {
            return '';
        }

        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html) ?? $html;
        $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? $html;
        $html = preg_replace('/\s(href|src|xlink:href|formaction|action)\s*=\s*(["\'])\s*(javascript:|data:|vbscript:)[^"\']*\2/iu', '', $html) ?? $html;

        return $html;
    }

    private static function stripInlineStyles(string $html): string
    {
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;
        $html = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? $html;
        $html = preg_replace('/\sbackground\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? $html;

        return $html;
    }
}
