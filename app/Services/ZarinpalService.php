<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Concerns\AuthorizesPaymentCallback;
use App\Services\Concerns\GuardsVerifiedPaymentAmount;
use App\Support\Money;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZarinpalService implements \App\Contracts\PaymentGatewayInterface
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
        $baseUrl = $sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://api.zarinpal.com/pg/v4/payment';
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

        if (! $payment) {
            return null;
        }

        if (! $this->callbackAuthorized($payment, $query)) {
            return null;
        }

        if ($payment->isSuccessful() && $payment->order?->isPaid()) {
            return $payment;
        }

        if ($status !== 'OK') {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'error_message' => 'پرداخت توسط کاربر لغو شد.',
                'gateway_payload' => array_merge($payment->gateway_payload ?? [], ['callback' => $query]),
            ]);
            if ($payment->order) {
                $this->orders->markFailed($payment->order);
            }

            return null;
        }

        $lock = $this->lockPayment($payment);

        if (! $lock->get()) {
            return $this->waitUntilSettled($payment);
        }

        try {
            $payment->refresh();

            if ($payment->isSuccessful() && $payment->order?->isPaid()) {
                return $payment;
            }

            $sandbox = $this->sandbox();
            $baseUrl = $sandbox
                ? 'https://sandbox.zarinpal.com/pg/v4/payment'
                : 'https://api.zarinpal.com/pg/v4/payment';

            $response = Http::post($baseUrl.'/verify.json', [
                'merchant_id' => $this->merchantId(),
                'amount' => Money::tomanToRials((int) $payment->amount),
                'authority' => $authority,
            ]);

            $code = $response->json('data.code');
            $payload = $response->json();

            if (in_array($code, [100, 101], true)) {
                if (! $this->amountAccepted($payment, $payload)) {
                    $this->rejectUntrustedAmount($payment, $payload);

                    return null;
                }

                $cardPan = $response->json('data.card_pan') ?? $response->json('data.cardPan');
                $cardHash = $response->json('data.card_hash') ?? $response->json('data.cardHash');

                try {
                    $this->orders->markPaid($payment->order, [
                        'status' => Payment::STATUS_SUCCESS,
                        'ref_id' => (string) $response->json('data.ref_id'),
                        'card_pan' => $cardPan,
                        'card_hash' => $cardHash,
                        'user_id' => $payment->user_id ?: $payment->order?->user_id,
                        'gateway_response' => $payload,
                        'verified_at' => now(),
                        'paid_at' => now(),
                        'error_message' => null,
                    ]);
                } catch (\RuntimeException) {
                    $payment->refresh();
                    if ($payment->order && ! $payment->order->isPaid()) {
                        $this->orders->markFailed($payment->order);
                    }

                    return null;
                }

                return $payment->fresh('order');
            }

            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'gateway_response' => $payload,
                'error_message' => (string) ($response->json('errors.message') ?? 'تأیید پرداخت ناموفق بود.'),
            ]);
            if ($payment->order) {
                $this->orders->markFailed($payment->order);
            }

            return null;
        } finally {
            $lock->release();
        }
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
