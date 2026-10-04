<?php

namespace App\Support;

use App\Models\Payment;

class PaymentAmount
{
    /**
     * The stored payment amount and, when the gateway reports one, the verified
     * amount must both match the order total. Store unit is toman; gateways speak rials.
     */
    public static function matches(Payment $payment, ?int $reportedRials, bool $reportedRequired): bool
    {
        $order = $payment->order;

        if (! $order) {
            return false;
        }

        $orderTotal = (int) $order->total;

        if ($orderTotal <= 0 || (int) $payment->amount !== $orderTotal) {
            return false;
        }

        if ($reportedRials === null) {
            return ! $reportedRequired;
        }

        return $reportedRials === Money::tomanToRials($orderTotal);
    }
}
