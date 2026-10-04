<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\Payment;
use RuntimeException;

class PaymentGatewayManager
{
    public function __construct(private PaymentGatewayRegistry $gateways) {}

    public function knows(string $name): bool
    {
        return $this->gateways->has($name);
    }

    /** @return list<array{name: string, label: string, enabled: bool}> */
    public function definitions(): array
    {
        return $this->gateways->definitions();
    }

    /** @return list<array{name: string, label: string}> */
    public function enabledOptions(): array
    {
        return $this->gateways->enabledOptions();
    }

    public function resolveForCheckout(?string $requested): string
    {
        return $this->gateways->resolveForCheckout($requested);
    }

    public function gateway(string $name): PaymentGatewayInterface
    {
        return $this->gateways->make($name);
    }

    public function requestPayment(Order $order, string $gateway): string
    {
        if (! $this->gateways->isEnabled($gateway)) {
            throw new RuntimeException('درگاه پرداخت انتخاب‌شده فعال نیست.');
        }

        return $this->gateway($gateway)->requestPayment($order);
    }

    /** @param  array<string, mixed>  $query */
    public function verifyCallback(string $gateway, array $query): ?Payment
    {
        if (! $this->gateways->has($gateway)) {
            return null;
        }

        return $this->gateway($gateway)->verifyCallback($query);
    }
}
