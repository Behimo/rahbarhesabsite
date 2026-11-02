<?php

namespace App\Services\Comments;

use App\Models\CmsSetting;
use Illuminate\Support\Arr;

class CommentSettings
{
    private ?array $resolved = null;

    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $defaults = config('comments.defaults', []);
        $stored = CmsSetting::get(config('comments.settings_key', 'comments_settings'));

        if (is_string($stored) && $stored !== '') {
            $decoded = json_decode($stored, true);
            $stored = is_array($decoded) ? $decoded : [];
        } elseif (! is_array($stored)) {
            $stored = [];
        }

        return $this->resolved = array_replace_recursive($defaults, $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    public function bool(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * @return list<string>
     */
    public function lines(string $key): array
    {
        $value = $this->get($key, []);

        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($line) => trim((string) $line),
            $value
        ), static fn ($line) => $line !== ''));
    }

    public function save(array $settings): void
    {
        $defaults = config('comments.defaults', []);
        $merged = array_replace_recursive($defaults, $settings);

        foreach (['profanity_words', 'blacklist_words', 'blocked_ips'] as $key) {
            if (array_key_exists($key, $settings) && is_array($settings[$key])) {
                $merged[$key] = array_values($settings[$key]);
            }
        }

        CmsSetting::set(config('comments.settings_key', 'comments_settings'), json_encode($merged, JSON_UNESCAPED_UNICODE));
        $this->resolved = null;
    }

    /**
     * Stored list wins after the admin saves. Before that, the built-in dictionary is used.
     *
     * @return list<string>
     */
    public function activeList(string $key): array
    {
        $stored = $this->lines($key);

        if ($stored !== [] || $this->bool('word_lists_managed')) {
            return $stored;
        }

        $dictionary = config('comment_dictionaries.'.$key, []);

        return is_array($dictionary) ? array_values(array_filter(array_map('strval', $dictionary))) : [];
    }

    public function forgetCache(): void
    {
        $this->resolved = null;
    }

    public function isGloballyEnabled(): bool
    {
        return $this->bool('enabled', true);
    }

    public function isTypeEnabled(string $typeKey): bool
    {
        return $this->isGloballyEnabled() && $this->bool("types.{$typeKey}", true);
    }

    public function typeKeyFor(string $morphClass): ?string
    {
        return match ($morphClass) {
            \App\Models\CmsPost::class => 'cms_post',
            \App\Models\ShopProduct::class => 'shop_product',
            default => null,
        };
    }
}
