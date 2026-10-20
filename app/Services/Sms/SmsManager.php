<?php

namespace App\Services\Sms;

use App\Contracts\OtpSmsDriver;
use Throwable;

class SmsManager
{
    public function __construct(private SmsDriverRegistry $registry) {}

    public function ensureConfigured(): void
    {
        $this->driver();
    }

    public function sendOtp(string $phone, string $code): void
    {
        $this->driver()->sendOtp($phone, $code);
    }

    private function driver(): OtpSmsDriver
    {
        $name = $this->registry->activeName();
        $definition = $this->registry->definitions()[$name];
        $class = $definition['class'] ?? null;

        if (! is_string($class) || ! is_subclass_of($class, OtpSmsDriver::class)) {
            throw new SmsConfigurationException;
        }

        $this->registry->configuration($name);

        try {
            $driver = app($class);
        } catch (Throwable $exception) {
            throw new SmsConfigurationException($exception);
        }

        if (! $driver instanceof OtpSmsDriver) {
            throw new SmsConfigurationException;
        }

        return $driver;
    }
}
