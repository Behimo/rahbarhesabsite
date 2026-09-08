<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function name(): string;

    public function requestPayment(Order $order): string;

    public function verifyCallback(array $query): ?Payment;
}
