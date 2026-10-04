<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\CmsSetting;
use InvalidArgumentException;
use RuntimeException;

class PaymentGatewayRegistry
{
    /** @var array<string, class-string<PaymentGatewayInterface>> */
    private array $drivers = [];

    public function __construct()
    {
        $this->register(ZibalService::class);
        $this->register(ZarinpalService::class);

        foreach (config('cms.payment_gateway_drivers', []) as $class) {
            if (is_string($class) && $class !== '') {
                $this->register($class);
            }
        }
    }

    /** @param  class-string<PaymentGatewayInterface>  $class */
    public function register(string $class): void
    {
        if (! is_subclass_of($class, PaymentGatewayInterface::class)) {
            throw new InvalidArgumentException("Payment gateway must implement PaymentGatewayInterface: {$class}");
        }

        $gateway = app($class);
        $this->drivers[$gateway->name()] = $class;
    }

    public function has(string $name): bool
    {
        return isset($this->drivers[$name]);
    }

    public function make(string $name): PaymentGatewayInterface
    {
        if (! isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Unknown payment gateway: {$name}");
        }

        return app($this->drivers[$name]);
    }

    /**
     * Every registered gateway, including ones the admin has switched off.
     *
     * @return list<array{name: string, label: string, enabled: bool}>
     */
    public function definitions(): array
    {
        $enabled = $this->enabledMap();
        $rows = [];

        foreach (array_keys($this->drivers) as $name) {
            $gateway = $this->make($name);
            $rows[] = [
                'name' => $name,
                'label' => $gateway->label(),
                'enabled' => $enabled[$name] ?? false,
            ];
        }

        return $rows;
    }

    /**
     * Gateways the storefront is allowed to start a payment with.
     *
     * @return list<array{name: string, label: string}>
     */
    public function enabledOptions(): array
    {
        $options = [];

        foreach ($this->definitions() as $gateway) {
            if ($gateway['enabled']) {
                $options[] = [
                    'name' => $gateway['name'],
                    'label' => $gateway['label'],
                ];
            }
        }

        return $options;
    }

    public function isEnabled(string $name): bool
    {
        return ($this->enabledMap()[$name] ?? false) === true;
    }

    /**
     * A single active gateway ignores the client value. Several active gateways
     * accept only a name that is currently enabled.
     */
    public function resolveForCheckout(?string $requested): string
    {
        $enabled = array_values(array_filter(
            array_keys($this->enabledMap()),
            fn (string $name) => $this->isEnabled($name),
        ));

        if ($enabled === []) {
            throw new RuntimeException('هیچ درگاه پرداختی فعال نیست.');
        }

        if (count($enabled) === 1) {
            return $enabled[0];
        }

        $requested = is_string($requested) ? $requested : '';

        if (! in_array($requested, $enabled, true)) {
            throw new RuntimeException('درگاه پرداخت انتخاب‌شده فعال نیست.');
        }

        return $requested;
    }

    /** @param  array<string, mixed>  $input */
    public function syncEnabled(array $input): void
    {
        $map = [];

        foreach (array_keys($this->drivers) as $name) {
            $map[$name] = filter_var($input[$name] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        if (! in_array(true, $map, true)) {
            throw new RuntimeException('حداقل یک درگاه باید فعال باشد.');
        }

        CmsSetting::set('payment_gateways', json_encode($map, JSON_THROW_ON_ERROR));
    }

    /**
     * Registered drivers with the values shown on the admin gateway page.
     *
     * @return list<array{name: string, label: string, enabled: bool, fields: list<array{key: string, label: string, type: string, value: mixed}>}>
     */
    public function panelRows(): array
    {
        $rows = [];

        foreach ($this->definitions() as $gateway) {
            $fields = [];

            foreach ($this->make($gateway['name'])->fields() as $field) {
                $fields[] = $field + [
                    'value' => $this->configValue($gateway['name'], $field['key']),
                ];
            }

            $rows[] = $gateway + ['fields' => $fields];
        }

        return $rows;
    }

    public function configValue(string $name, string $key, mixed $default = null): mixed
    {
        $saved = $this->credentialMap();

        if (isset($saved[$name]) && is_array($saved[$name]) && array_key_exists($key, $saved[$name])) {
            $value = $saved[$name][$key];

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return config('cms.'.$name.'.'.$key, $default);
    }

    /** @param  array<string, mixed>  $input */
    public function syncCredentials(array $input): void
    {
        $stored = [];

        foreach (array_keys($this->drivers) as $name) {
            $incoming = is_array($input[$name] ?? null) ? $input[$name] : [];

            foreach ($this->make($name)->fields() as $field) {
                $key = $field['key'];
                $raw = $incoming[$key] ?? null;

                if (($field['type'] ?? 'text') === 'boolean') {
                    $stored[$name][$key] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);

                    continue;
                }

                $value = is_scalar($raw) ? trim((string) $raw) : '';

                if ($value !== '') {
                    $stored[$name][$key] = mb_substr($value, 0, 255);
                }
            }
        }

        CmsSetting::set('payment_gateway_credentials', json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    private function credentialMap(): array
    {
        $saved = json_decode((string) CmsSetting::get('payment_gateway_credentials', ''), true);

        return is_array($saved) ? $saved : [];
    }

    /** @return array<string, bool> */
    private function enabledMap(): array
    {
        $saved = json_decode((string) CmsSetting::get('payment_gateways', ''), true);
        $default = (string) config('cms.payment_gateway', 'zibal');
        $map = [];

        foreach (array_keys($this->drivers) as $name) {
            if (is_array($saved)) {
                $map[$name] = (bool) ($saved[$name] ?? false);
            } else {
                $map[$name] = $name === $default;
            }
        }

        return $map;
    }
}
