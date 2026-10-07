<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function name(): string;

    public function label(): string;

    /**
     * Values the admin panel can store for this driver. Empty falls back to config.
     *
     * @return list<array{key: string, label: string, type: 'text'|'boolean'}>
     */
    public function fields(): array;

    public function requestPayment(Order $order): string;

    public function verifyCallback(array $query): ?Payment;

    /**
     * Settle or fail a pending payment when the browser never returned.
     * Must not call the gateway once the order is already paid.
     */
    public function reconcile(Payment $payment): void;

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
