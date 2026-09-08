<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;

class PaymentGatewayManager
{
    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?? config('cms.payment_gateway', 'zibal');

        return match ($name) {
            'zarinpal' => app(ZarinpalService::class),
            default => app(ZibalService::class),
        };
    }

    public function requestPayment(Order $order, ?string $gateway = null): string
    {
        return $this->gateway($gateway)->requestPayment($order);
    }

    public function verifyCallback(string $gateway, array $query): ?\App\Models\Payment
    {
        return $this->gateway($gateway)->verifyCallback($query);
    }
}
