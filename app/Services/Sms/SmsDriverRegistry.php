<?php

namespace App\Services\Sms;

use App\Models\CmsSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SmsDriverRegistry
{
    private const STATE_KEY = 'sms.driver_state';

    public function definitions(): array
    {
        return config('sms.drivers', []);
    }

    public function selectedName(): ?string
    {
        $state = $this->state();
        $name = $state === null ? config('sms.default') : ($state['default'] ?? null);

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function isEnabled(string $name): bool
    {
        $state = $this->state();

        return $state === null
            ? $name === $this->selectedName()
            : ($state['enabled'][$name] ?? false) === true;
    }

    public function activeName(): string
    {
        $name = $this->selectedName();

        if ($name === null || ! isset($this->definitions()[$name]) || ! $this->isEnabled($name)) {
            throw new SmsConfigurationException;
        }

        return $name;
    }

    public function configuration(string $name): array
    {
        $definition = $this->definitions()[$name] ?? null;
        if (! is_array($definition) || ! is_array($definition['fields'] ?? null)) {
            throw new SmsConfigurationException;
        }

        $stored = $this->state()['settings'][$name] ?? [];
        $configuration = [];

        foreach ($definition['fields'] as $key => $field) {
            $value = ($field['type'] ?? null) === 'secret'
                ? $this->secret($name, $key)
                : ($stored[$key] ?? null);

            if (($field['required'] ?? false) && ($value === null || $value === '')) {
                throw new SmsConfigurationException;
            }

            if ($value !== null && $value !== '') {
                $configuration[$key] = $this->normalizedValue($value, $field['type'] ?? '');
            }
        }

        return $configuration;
    }

    public function panelRows(): array
    {
        $state = $this->state();
        $rows = [];

        foreach ($this->definitions() as $name => $definition) {
            $fields = [];
            foreach ($definition['fields'] as $key => $field) {
                $secret = $field['type'] === 'secret';
                $fields[] = [
                    'key' => $key,
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'value' => $secret ? '' : ($state['settings'][$name][$key] ?? ''),
                    'stored' => $secret && $this->hasSecret($name, $key),
                ];
            }

            $rows[] = [
                'name' => $name,
                'label' => $definition['label'],
                'enabled' => $this->isEnabled($name),
                'selected' => $name === $this->selectedName(),
                'fields' => $fields,
            ];
        }

        return $rows;
    }

    public function save(?string $selected, array $enabled, array $settings, array $clear): void
    {
        $definitions = $this->definitions();
        $this->rejectUnknownKeys($enabled, array_keys($definitions), 'enabled');
        $this->rejectUnknownKeys($settings, array_keys($definitions), 'settings');
        $this->rejectUnknownKeys($clear, array_keys($definitions), 'clear');

        if ($selected !== null && ! array_key_exists($selected, $definitions)) {
            throw ValidationException::withMessages(['default' => 'درایور اصلی معتبر نیست.']);
        }

        $state = $this->state() ?? [];
        $next = [
            'default' => $selected,
            'enabled' => [],
            'settings' => is_array($state['settings'] ?? null) ? $state['settings'] : [],
        ];
        $secrets = [];

        foreach ($definitions as $name => $definition) {
            $next['enabled'][$name] = (bool) ($enabled[$name] ?? false);
            $this->rejectUnknownKeys($settings[$name] ?? [], array_keys($definition['fields']), "settings.$name");
            $this->rejectUnknownKeys($clear[$name] ?? [], array_keys($definition['fields']), "clear.$name");

            foreach ($definition['fields'] as $key => $field) {
                $value = $settings[$name][$key] ?? null;
                if ($field['type'] === 'secret') {
                    $value = is_string($value) ? trim($value) : $value;
                    if ($value !== null && $value !== '') {
                        $secrets[$name][$key] = $value;
                    } elseif ($clear[$name][$key] ?? false) {
                        $secrets[$name][$key] = null;
                    }
                } elseif (array_key_exists($key, $settings[$name] ?? [])) {
                    $next['settings'][$name][$key] = $value === null || $value === '' ? null : $this->normalizedValue($value, $field['type']);
                }
            }

            if (! $next['enabled'][$name]) {
                continue;
            }

            foreach ($definition['fields'] as $key => $field) {
                $value = $field['type'] === 'secret'
                    ? (array_key_exists($key, $secrets[$name] ?? []) ? $secrets[$name][$key] : $this->secret($name, $key))
                    : ($next['settings'][$name][$key] ?? null);

                if (($field['required'] ?? false) && ($value === null || $value === '')) {
                    throw ValidationException::withMessages(["settings.$name.$key" => "برای فعال‌سازی {$definition['label']}، {$field['label']} را وارد کنید."]);
                }
            }
        }

        if ($selected !== null && ! $next['enabled'][$selected]) {
            throw ValidationException::withMessages(['default' => 'درایور اصلی باید فعال باشد.']);
        }

        DB::transaction(function () use ($next, $secrets): void {
            $keys = [self::STATE_KEY];
            foreach ($secrets as $name => $fields) {
                foreach ($fields as $key => $value) {
                    $settingKey = $this->secretKey($name, $key);
                    $keys[] = $settingKey;
                    CmsSetting::set($settingKey, $value === null ? null : Crypt::encryptString($value));
                }
            }

            CmsSetting::set(self::STATE_KEY, json_encode($next, JSON_THROW_ON_ERROR));

            // A worker may have cached the previous values before this commit.
            DB::afterCommit(function () use ($keys): void {
                foreach ($keys as $key) {
                    Cache::forget("cms_setting.$key");
                }
            });
        });
    }

    private function state(): ?array
    {
        $raw = CmsSetting::get(self::STATE_KEY);
        if ($raw === null) {
            return null;
        }

        $state = json_decode($raw, true);
        if (! is_array($state)
            || ! array_key_exists('default', $state)
            || ($state['default'] !== null && ! is_string($state['default']))
            || ! is_array($state['enabled'] ?? null)
            || ! is_array($state['settings'] ?? null)) {
            throw new SmsConfigurationException;
        }

        return $state;
    }

    private function hasSecret(string $name, string $key): bool
    {
        $value = CmsSetting::get($this->secretKey($name, $key));

        return is_string($value) && $value !== '';
    }

    private function secret(string $name, string $key): ?string
    {
        $value = CmsSetting::get($this->secretKey($name, $key));
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable $exception) {
            throw new SmsConfigurationException($exception);
        }
    }

    private function secretKey(string $name, string $key): string
    {
        return "sms.secret.$name.$key";
    }

    private function normalizedValue(mixed $value, string $type): string|int
    {
        if ($type === 'integer' && filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) !== false) {
            return (int) $value;
        }

        if ($type === 'identifier' && is_string($value) && preg_match('/\A[A-Za-z][A-Za-z0-9_]*\z/D', $value) && strlen($value) <= 64) {
            return $value;
        }

        if (in_array($type, ['secret', 'text'], true) && is_string($value) && $value !== '') {
            return $value;
        }

        throw new SmsConfigurationException;
    }

    private function rejectUnknownKeys(array $values, array $allowed, string $path): void
    {
        if (array_diff(array_keys($values), $allowed) !== []) {
            throw ValidationException::withMessages([$path => 'تنظیمات درایور معتبر نیست.']);
        }
    }
}
