<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZibalService implements PaymentGatewayInterface
{
    public function __construct(private OrderService $orders) {}

    public function name(): string
    {
        return 'zibal';
    }

    public function requestPayment(Order $order): string
    {
        if ($order->total <= 0) {
            $this->orders->markPaid($order);

            return route('checkout.success', $order);
        }

        $merchant = config('cms.zibal.merchant');
        $baseUrl = rtrim(config('cms.zibal.base_url', 'https://gateway.zibal.ir'), '/');
        $amountRials = Money::tomanToRials((int) $order->total);

        $response = Http::post($baseUrl.'/v1/request', [
            'merchant' => $merchant,
            'amount' => $amountRials,
            'callbackUrl' => route('checkout.callback', ['gateway' => 'zibal']),
            'description' => 'سفارش '.$order->order_number,
            'orderId' => $order->order_number,
        ]);

        $trackId = $response->json('trackId');
        $result = $response->json('result');

        if ($result !== 100 || ! $trackId) {
            Log::error('Zibal request failed', ['response' => $response->json()]);
            throw new \RuntimeException('خطا در اتصال به درگاه زیبال.');
        }

        Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'zibal',
            'authority' => (string) $trackId,
            'amount' => $order->total,
            'status' => Payment::STATUS_PENDING,
            'gateway_payload' => ['amount_rial' => $amountRials],
            'gateway_response' => $response->json(),
        ]);

        return $baseUrl.'/start/'.$trackId;
    }

    public function verifyCallback(array $query): ?Payment
    {
        $trackId = $query['trackId'] ?? null;
        $success = ($query['success'] ?? null) == '1';

        if (! $trackId) {
            return null;
        }

        $payment = Payment::query()->where('gateway', 'zibal')->where('authority', (string) $trackId)->first();

        if (! $payment) {
            return null;
        }

        if ($payment->isSuccessful() && $payment->order?->isPaid()) {
            return $payment;
        }

        if (! $success) {
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

            $baseUrl = rtrim(config('cms.zibal.base_url', 'https://gateway.zibal.ir'), '/');
            $response = Http::post($baseUrl.'/v1/verify', [
                'merchant' => config('cms.zibal.merchant'),
                'trackId' => $trackId,
            ]);

            $result = $response->json('result');
            $payload = $response->json();

            // 100 = first verify, 201 = already verified
            if (in_array($result, [100, 201], true)) {
                $payment->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'ref_id' => (string) ($payload['refNumber'] ?? $payment->ref_id),
                    'card_pan' => $payload['cardNumber'] ?? $payment->card_pan,
                    'card_hash' => $payload['cardHash'] ?? $payment->card_hash,
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
                'error_message' => (string) ($payload['message'] ?? 'تأیید پرداخت ناموفق بود.'),
            ]);
            $payment->order?->update(['status' => Order::STATUS_FAILED]);

            return null;
        } finally {
            $lock->release();
        }
    }
}
