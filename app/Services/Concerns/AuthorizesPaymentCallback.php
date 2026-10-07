<?php

namespace App\Services\Concerns;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait AuthorizesPaymentCallback
{
    protected function newCallbackToken(): string
    {
        return Str::random(40);
    }

    protected function rememberCallbackToken(string $authority, string $token): void
    {
        session(['payment_callbacks.'.$authority => $token]);
    }

    /** @param  array<string, mixed>  $query */
    protected function callbackAuthorized(Payment $payment, array $query): bool
    {
        $expected = (string) ($payment->gateway_payload['callback_token'] ?? '');
        $fromQuery = (string) ($query['token'] ?? '');
        $fromSession = (string) session('payment_callbacks.'.$payment->authority, '');
        $given = $fromQuery !== '' ? $fromQuery : $fromSession;

        return $expected !== '' && $given !== '' && hash_equals($expected, $given);
    }

    protected function waitUntilSettled(Payment $payment): ?Payment
    {
        $deadline = microtime(true) + 3;

        do {
            usleep(200000);
            $payment->refresh();

            if ($payment->isSuccessful() && $payment->order?->isPaid()) {
                return $payment;
            }
        } while (microtime(true) < $deadline);

        return null;
    }

    protected function reusablePaymentUrl(int $orderId, string $gateway): ?string
    {
        $existing = Payment::query()
            ->where('order_id', $orderId)
            ->where('gateway', $gateway)
            ->where('status', Payment::STATUS_PENDING)
            ->latest('id')
            ->first();

        $url = $existing->gateway_payload['start_url'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    protected function lockPayment(Payment $payment): \Illuminate\Contracts\Cache\Lock
    {
        return Cache::lock('payment:verify:'.$payment->id, 30);
    }
}
