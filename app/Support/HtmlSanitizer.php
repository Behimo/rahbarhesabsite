<?php

namespace App\Support;

class HtmlSanitizer
{
    public static function clean(?string $html): string
    {
        $html = (string) $html;

        if (trim($html) === '') {
            return '';
        }

        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html) ?? $html;
        $allowed = '<p><br><strong><em><b><i><u><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6><blockquote><table><thead><tbody><tr><th><td><figure><figcaption><span><div><hr><pre><code>';
        $html = strip_tags($html, $allowed);
        $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? $html;
        $html = preg_replace('/\s(href|src)\s*=\s*(["\'])\s*(javascript:|data:|vbscript:)[^"\']*\2/iu', '', $html) ?? $html;

        return $html;
    }
}
