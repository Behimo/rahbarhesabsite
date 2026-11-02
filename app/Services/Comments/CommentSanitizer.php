<?php

namespace App\Services\Comments;

class CommentSanitizer
{
    public function sanitize(?string $body): string
    {
        $body = (string) $body;

        if ($body === '') {
            return '';
        }

        // Decode entities first so encoded tags cannot bypass strip_tags.
        $previous = '';
        $decoded = $body;
        for ($i = 0; $i < 3 && $decoded !== $previous; $i++) {
            $previous = $decoded;
            $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $decoded = str_replace("\0", '', $decoded);

        // Strip bidi overrides and other invisible control chars (keep \n \r \t).
        $decoded = preg_replace('/[\x{202A}-\x{202E}\x{2066}-\x{2069}\x{200E}\x{200F}]/u', '', $decoded) ?? $decoded;
        $decoded = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $decoded) ?? $decoded;

        // Remove dangerous elements including their inner content (strip_tags alone keeps text).
        $decoded = preg_replace('#<(script|style|iframe|object|embed|svg|math)\b[^>]*>.*?</\1>#is', '', $decoded) ?? $decoded;
        $decoded = preg_replace('#<(script|style|iframe|object|embed|svg|math)\b[^>]*/?>#is', '', $decoded) ?? $decoded;

        $decoded = strip_tags($decoded);

        // Normalize newlines and collapse excessive blank lines.
        $decoded = str_replace(["\r\n", "\r"], "\n", $decoded);
        $decoded = preg_replace("/\n{3,}/", "\n\n", $decoded) ?? $decoded;
        $decoded = preg_replace('/[ \t]+/u', ' ', $decoded) ?? $decoded;

        return trim($decoded);
    }
}
