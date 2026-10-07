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

class ZarinpalService implements PaymentGatewayInterface
{
    use AuthorizesPaymentCallback;
    use GuardsVerifiedPaymentAmount;

    public function __construct(private OrderService $orders) {}

    public function name(): string
    {
        return 'zarinpal';
    }

    public function label(): string
    {
        return 'زرین‌پال';
    }

    public function fields(): array
    {
        return [
            ['key' => 'merchant_id', 'label' => 'مرچنت', 'type' => 'text'],
            ['key' => 'sandbox', 'label' => 'حالت آزمایشی', 'type' => 'boolean'],
        ];
    }

    public function reportedAmountRials(array $verifyPayload): ?int
    {
        $amount = $verifyPayload['data']['amount'] ?? $verifyPayload['amount'] ?? null;

        return is_numeric($amount) ? (int) $amount : null;
    }

    public function requiresReportedAmount(): bool
    {
        return false;
    }

    public function requestPayment(Order $order): string
    {
        if ($order->total <= 0) {
            $this->orders->markPaid($order);

            return route('checkout.success', $order);
        }

        $merchantId = $this->merchantId();
        $sandbox = $this->sandbox();
        $baseUrl = $this->paymentBaseUrl();
        if ($existingUrl = $this->reusablePaymentUrl($order->id, $this->name())) {
            return $existingUrl;
        }

        $amountRials = Money::tomanToRials((int) $order->total);
        $callbackToken = $this->newCallbackToken();

        $response = Http::post($baseUrl.'/request.json', [
            'merchant_id' => $merchantId,
            'amount' => $amountRials,
            'callback_url' => route('checkout.callback', ['gateway' => 'zarinpal', 'token' => $callbackToken]),
            'description' => 'سفارش '.$order->order_number,
            'metadata' => ['order_id' => $order->id],
        ]);

        $data = $response->json('data');

        if ($response->json('errors') || empty($data['authority'])) {
            Log::error('Zarinpal request failed', ['response' => $response->json()]);
            throw new \RuntimeException('خطا در اتصال به درگاه پرداخت.');
        }

        Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'zarinpal',
            'authority' => $data['authority'],
            'amount' => $order->total,
            'status' => Payment::STATUS_PENDING,
            'gateway_payload' => [
                'amount_rial' => $amountRials,
                'callback_token' => $callbackToken,
                'start_url' => ($sandbox
                    ? 'https://sandbox.zarinpal.com/pg/StartPay/'
                    : 'https://www.zarinpal.com/pg/StartPay/').$data['authority'],
            ],
            'gateway_response' => $response->json(),
        ]);

        $this->rememberCallbackToken((string) $data['authority'], $callbackToken);

        return $this->reusablePaymentUrl($order->id, $this->name())
            ?? (($sandbox
                ? 'https://sandbox.zarinpal.com/pg/StartPay/'
                : 'https://www.zarinpal.com/pg/StartPay/').$data['authority']);
    }

    public function verifyCallback(array $query): ?Payment
    {
        $authority = $query['Authority'] ?? null;
        $status = $query['Status'] ?? null;

        if (! $authority) {
            return null;
        }

        $payment = Payment::query()->where('gateway', 'zarinpal')->where('authority', $authority)->first();

        if (! $payment || ! $this->callbackAuthorized($payment, $query)) {
            return null;
        }

        if ($payment->isSuccessful() && $payment->order?->isPaid()) {
            return $payment;
        }

        if ($payment->status === Payment::STATUS_FAILED) {
            return null;
        }

        if ($status !== 'OK') {
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
            return $this->runWithOrderLock($payment, function () use ($payment, $authority) {
                $resolved = $this->resolveAlreadyPaidOrder($payment);

                if ($resolved !== false) {
                    return $resolved?->fresh('order');
                }

                $response = Http::post($this->paymentBaseUrl().'/verify.json', [
                    'merchant_id' => $this->merchantId(),
                    'amount' => Money::tomanToRials((int) $payment->amount),
                    'authority' => $authority,
                ]);

                $payload = $response->json();

                return $this->applyZarinpalVerification($payment, is_array($payload) ? $payload : [], true);
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
                $response = Http::timeout(10)->post($this->paymentBaseUrl().'/verify.json', [
                    'merchant_id' => $this->merchantId(),
                    'amount' => Money::tomanToRials((int) $payment->amount),
                    'authority' => $payment->authority,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Zarinpal reconcile request failed.', [
                    'payment_id' => $payment->id,
                    'message' => $e->getMessage(),
                ]);

                return;
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                Log::warning('Zarinpal reconcile returned an unreadable response.', [
                    'payment_id' => $payment->id,
                ]);

                return;
            }

            $this->applyZarinpalVerification($payment, $payload, false);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyZarinpalVerification(Payment $payment, array $payload, bool $failOnUnknown): ?Payment
    {
        $code = $payload['data']['code'] ?? null;
        $code = is_numeric($code) ? (int) $code : null;

        // 100 = first verify, 101 = already verified
        if (in_array($code, [100, 101], true)) {
            if (! $this->amountAccepted($payment, $payload)) {
                $this->rejectUntrustedAmount($payment, $payload);

                return null;
            }

            $cardPan = $payload['data']['card_pan'] ?? $payload['data']['cardPan'] ?? null;
            $cardHash = $payload['data']['card_hash'] ?? $payload['data']['cardHash'] ?? null;

            try {
                $this->orders->markPaid($payment->order, [
                    'status' => Payment::STATUS_SUCCESS,
                    'ref_id' => (string) ($payload['data']['ref_id'] ?? ''),
                    'card_pan' => $cardPan,
                    'card_hash' => $cardHash,
                    'user_id' => $payment->user_id ?: $payment->order?->user_id,
                    'gateway_response' => $payload,
                    'verified_at' => now(),
                    'paid_at' => now(),
                    'error_message' => null,
                ], $payment);
            } catch (\RuntimeException $e) {
                Log::critical('Zarinpal payment was verified but the order could not be fulfilled.', [
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

        $errorCode = $payload['errors']['code'] ?? null;
        $errorCode = is_numeric($errorCode) ? (int) $errorCode : null;
        // -50 amount mismatch, -51 unpaid session, -54 invalid authority.
        $finalFailure = in_array($errorCode, [-50, -51, -54], true);

        if (! $finalFailure && ! $failOnUnknown) {
            Log::warning('Zarinpal reconcile left the payment pending.', [
                'payment_id' => $payment->id,
                'code' => $code,
                'error_code' => $errorCode,
            ]);

            return null;
        }

        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'gateway_response' => $payload,
            'error_message' => (string) ($payload['errors']['message'] ?? 'تأیید پرداخت ناموفق بود.'),
        ]);
        $this->orders->markFailedFromPayment($payment);

        return null;
    }

    private function paymentBaseUrl(): string
    {
        return $this->sandbox()
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://api.zarinpal.com/pg/v4/payment';
    }

    private function merchantId(): mixed
    {
        return app(PaymentGatewayRegistry::class)->configValue($this->name(), 'merchant_id');
    }

    private function sandbox(): bool
    {
        return filter_var(
            app(PaymentGatewayRegistry::class)->configValue($this->name(), 'sandbox', true),
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
