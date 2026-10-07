<?php

namespace App\Services\Concerns;

use App\Models\Payment;
use App\Services\OrderService;
use App\Support\PaymentAmount;
use Illuminate\Support\Facades\Log;

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
        Log::critical('Gateway verify succeeded but the amount did not match the order.', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
        ]);

        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'gateway_response' => $payload,
            'error_message' => 'مبلغ تأییدشده با مبلغ سفارش یکسان نیست.',
        ]);

        app(OrderService::class)->markFailedFromPayment($payment);
    }
}
