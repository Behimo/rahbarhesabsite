<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZarinpalService implements \App\Contracts\PaymentGatewayInterface
{
    public function __construct(private OrderService $orders) {}

    public function name(): string
    {
        return 'zarinpal';
    }

    public function requestPayment(Order $order): string
    {
        if ($order->total <= 0) {
            $this->orders->markPaid($order);

            return route('checkout.success', $order);
        }

        $merchantId = config('cms.zarinpal.merchant_id');
        $sandbox = config('cms.zarinpal.sandbox', true);
        $baseUrl = $sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://api.zarinpal.com/pg/v4/payment';
        $amountRials = Money::tomanToRials((int) $order->total);

        $response = Http::post($baseUrl.'/request.json', [
            'merchant_id' => $merchantId,
            'amount' => $amountRials,
            'callback_url' => route('checkout.callback', ['gateway' => 'zarinpal']),
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
            'gateway_payload' => ['amount_rial' => $amountRials],
            'gateway_response' => $response->json(),
        ]);

        $gatewayUrl = $sandbox
            ? 'https://sandbox.zarinpal.com/pg/StartPay/'
            : 'https://www.zarinpal.com/pg/StartPay/';

        return $gatewayUrl.$data['authority'];
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

        if ($payment->isSuccessful() && $payment->order?->isPaid()) {
            return $payment;
        }

        if ($status !== 'OK') {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'error_message' => 'پرداخت توسط کاربر لغو شد.',
                'gateway_payload' => $query,
            ]);
            $payment->order?->update(['status' => Order::STATUS_FAILED]);

            return null;
        }

        $lock = Cache::lock('payment:verify:'.$payment->id, 30);

        if (! $lock->get()) {
            usleep(250000);
            $payment->refresh();

            return $payment->isSuccessful() ? $payment : null;
        }

        try {
            $payment->refresh();

            if ($payment->isSuccessful() && $payment->order?->isPaid()) {
                return $payment;
            }

            $sandbox = config('cms.zarinpal.sandbox', true);
            $baseUrl = $sandbox
                ? 'https://sandbox.zarinpal.com/pg/v4/payment'
                : 'https://api.zarinpal.com/pg/v4/payment';

            $response = Http::post($baseUrl.'/verify.json', [
                'merchant_id' => config('cms.zarinpal.merchant_id'),
                'amount' => Money::tomanToRials((int) $payment->amount),
                'authority' => $authority,
            ]);

            $code = $response->json('data.code');
            $payload = $response->json();

            if (in_array($code, [100, 101], true)) {
                $cardPan = $response->json('data.card_pan') ?? $response->json('data.cardPan');
                $cardHash = $response->json('data.card_hash') ?? $response->json('data.cardHash');

                $payment->update([
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

                $this->orders->markPaid($payment->order);

                return $payment->fresh('order');
            }

            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'gateway_response' => $payload,
                'error_message' => (string) ($response->json('errors.message') ?? 'تأیید پرداخت ناموفق بود.'),
            ]);
            $payment->order?->update(['status' => Order::STATUS_FAILED]);

            return null;
        } finally {
            $lock->release();
        }
    }
}
