<?php

namespace App\Services\Concerns;

use App\Models\Payment;
use App\Services\OrderService;
use App\Support\PaymentAmount;

trait GuardsVerifiedPaymentAmount
{
    /** @param  array<string, mixed>  $payload */
    protected function amountAccepted(Payment $payment, array $payload): bool
    {
        return PaymentAmount::matches(
            $payment,
            $this->reportedAmountRials($payload),
            $this->requiresReportedAmount(),
        );
    }

    /** @param  array<string, mixed>  $payload */
    protected function rejectUntrustedAmount(Payment $payment, array $payload): void
    {
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'gateway_response' => $payload,
            'error_message' => 'مبلغ تأییدشده با مبلغ سفارش یکسان نیست.',
        ]);
        if ($payment->order) {
            app(OrderService::class)->markFailed($payment->order);
        }
    }
}
