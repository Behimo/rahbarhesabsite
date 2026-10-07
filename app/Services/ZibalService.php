<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Concerns\AuthorizesPaymentCallback;
use App\Services\Concerns\GuardsVerifiedPaymentAmount;
use App\Support\Money;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZibalService implements PaymentGatewayInterface
{
    use AuthorizesPaymentCallback;
    use GuardsVerifiedPaymentAmount;

    public function __construct(private OrderService $orders) {}

    public function name(): string
    {
        return 'zibal';
    }

    public function label(): string
    {
        return 'زیبال';
    }

    public function fields(): array
    {
        return [
            ['key' => 'merchant', 'label' => 'مرچنت', 'type' => 'text'],
            ['key' => 'base_url', 'label' => 'آدرس درگاه', 'type' => 'text'],
        ];
    }

    public function reportedAmountRials(array $verifyPayload): ?int
    {
        $amount = $verifyPayload['amount'] ?? null;

        return is_numeric($amount) ? (int) $amount : null;
    }

    public function requiresReportedAmount(): bool
    {
        return true;
    }

    public function requestPayment(Order $order): string
    {
        if ($order->total <= 0) {
            $this->orders->markPaid($order);

            return route('checkout.success', $order);
        }

        if ($existingUrl = $this->reusablePaymentUrl($order->id, $this->name())) {
            return $existingUrl;
        }

        $merchant = $this->setting('merchant');
        $baseUrl = $this->baseUrl();
        $amountRials = Money::tomanToRials((int) $order->total);
        $callbackToken = $this->newCallbackToken();

        $response = Http::post($baseUrl.'/v1/request', [
            'merchant' => $merchant,
            'amount' => $amountRials,
            'callbackUrl' => route('checkout.callback', ['gateway' => 'zibal', 'token' => $callbackToken]),
            'description' => 'سفارش '.$order->order_number,
            'orderId' => $order->order_number,
        ]);

        $trackId = $response->json('trackId');
        $result = $response->json('result');

        if ($result !== 100 || ! $trackId) {
            Log::error('Zibal request failed', ['response' => $response->json()]);
            throw new \RuntimeException('خطا در اتصال به درگاه زیبال.');
        }

        $startUrl = $baseUrl.'/start/'.$trackId;

        Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'zibal',
            'authority' => (string) $trackId,
            'amount' => $order->total,
            'status' => Payment::STATUS_PENDING,
            'gateway_payload' => [
                'amount_rial' => $amountRials,
                'callback_token' => $callbackToken,
                'start_url' => $startUrl,
            ],
            'gateway_response' => $response->json(),
        ]);

        $this->rememberCallbackToken((string) $trackId, $callbackToken);

        return $startUrl;
    }

    public function verifyCallback(array $query): ?Payment
    {
        $trackId = $query['trackId'] ?? null;
        $success = ($query['success'] ?? null) == '1';

        if (! $trackId) {
            return null;
        }

        $payment = Payment::query()->where('gateway', 'zibal')->where('authority', (string) $trackId)->first();

        if (! $payment || ! $this->callbackAuthorized($payment, $query)) {
            return null;
        }

        if ($payment->isSuccessful() && $payment->order?->isPaid()) {
            return $payment;
        }

        if ($payment->status === Payment::STATUS_FAILED) {
            return null;
        }

        if (! $success) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'error_message' => 'پرداخت توسط کاربر لغو شد.',
                'gateway_payload' => array_merge($payment->gateway_payload ?? [], ['callback' => $query]),
            ]);
            $this->orders->markFailedFromPayment($payment);

            return null;
        }

        $lock = $this->lockPayment($payment);

        if (! $lock->get()) {
            return $this->waitUntilSettled($payment);
        }

        try {
            return $this->runWithOrderLock($payment, function () use ($payment, $trackId) {
                $resolved = $this->resolveAlreadyPaidOrder($payment);

                if ($resolved !== false) {
                    return $resolved?->fresh('order');
                }

                $response = Http::post($this->baseUrl().'/v1/verify', [
                    'merchant' => $this->setting('merchant'),
                    'trackId' => $trackId,
                ]);

                $payload = $response->json();

                return $this->applyZibalVerification($payment, is_array($payload) ? $payload : [], true);
            });
        } finally {
            $lock->release();
        }
    }

    public function reconcile(Payment $payment): void
    {
        if ($payment->gateway !== $this->name() || $payment->status !== Payment::STATUS_PENDING || ! $payment->authority) {
            return;
        }

        $this->runWithOrderLock($payment, function () use ($payment) {
            $resolved = $this->resolveAlreadyPaidOrder($payment);

            if ($resolved !== false || $payment->status !== Payment::STATUS_PENDING) {
                return;
            }

            try {
                $response = Http::timeout(10)->post($this->baseUrl().'/v1/verify', [
                    'merchant' => $this->setting('merchant'),
                    'trackId' => $payment->authority,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Zibal reconcile request failed.', [
                    'payment_id' => $payment->id,
                    'message' => $e->getMessage(),
                ]);

                return;
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                Log::warning('Zibal reconcile returned an unreadable response.', [
                    'payment_id' => $payment->id,
                ]);

                return;
            }

            $this->applyZibalVerification($payment, $payload, false);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyZibalVerification(Payment $payment, array $payload, bool $failOnUnknown): ?Payment
    {
        $result = $payload['result'] ?? null;

        // 100 = first verify, 201 = already verified
        if (in_array($result, [100, 201], true)) {
            if (! $this->amountAccepted($payment, $payload)) {
                $this->rejectUntrustedAmount($payment, $payload);

                return null;
            }

            try {
                $this->orders->markPaid($payment->order, [
                    'status' => Payment::STATUS_SUCCESS,
                    'ref_id' => (string) ($payload['refNumber'] ?? $payment->ref_id),
                    'card_pan' => $payload['cardNumber'] ?? $payment->card_pan,
                    'card_hash' => $payload['cardHash'] ?? $payment->card_hash,
                    'user_id' => $payment->user_id ?: $payment->order?->user_id,
                    'gateway_response' => $payload,
                    'verified_at' => now(),
                    'paid_at' => now(),
                    'error_message' => null,
                ], $payment);
            } catch (\RuntimeException $e) {
                Log::critical('Zibal payment was verified but the order could not be fulfilled.', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'message' => $e->getMessage(),
                ]);
                $payment->refresh();
                if ($payment->order && ! $payment->order->isPaid()) {
                    $this->orders->markFailedFromPayment($payment);
                }

                return null;
            }

            return $payment->fresh('order');
        }

        // 202 = unpaid or failed, 203 = unknown track id. Other codes can be transient.
        $finalFailure = in_array($result, [202, 203], true);

        if (! $finalFailure && ! $failOnUnknown) {
            Log::warning('Zibal reconcile left the payment pending.', [
                'payment_id' => $payment->id,
                'result' => $result,
            ]);

            return null;
        }

        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'gateway_response' => $payload,
            'error_message' => (string) ($payload['message'] ?? 'تأیید پرداخت ناموفق بود.'),
        ]);
        $this->orders->markFailedFromPayment($payment);

        return null;
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        return app(PaymentGatewayRegistry::class)->configValue($this->name(), $key, $default);
    }

    private function baseUrl(): string
    {
        return rtrim((string) $this->setting('base_url', 'https://gateway.zibal.ir'), '/');
    }
}
