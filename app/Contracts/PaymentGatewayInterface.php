<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function name(): string;

    public function label(): string;

    public function requestPayment(Order $order): string;

    public function verifyCallback(array $query): ?Payment;

    /**
     * Paid amount echoed by the gateway verify response, in rials.
     * Null when this gateway does not return the amount.
     *
     * @param  array<string, mixed>  $verifyPayload
     */
    public function reportedAmountRials(array $verifyPayload): ?int;

    /** When true, a missing reported amount rejects the payment. */
    public function requiresReportedAmount(): bool;
}
