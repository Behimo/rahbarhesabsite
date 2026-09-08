<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\Payment;
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
            $order->update(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
            $this->orders->fulfill($order);

            return route('checkout.success', $order);
        }

        $merchant = config('cms.zibal.merchant');
        $baseUrl = rtrim(config('cms.zibal.base_url', 'https://gateway.zibal.ir'), '/');

        $response = Http::post($baseUrl.'/v1/request', [
            'merchant' => $merchant,
            'amount' => $order->total,
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
            'gateway' => 'zibal',
            'authority' => (string) $trackId,
            'amount' => $order->total,
            'status' => Payment::STATUS_PENDING,
            'gateway_response' => $response->json(),
        ]);

        return $baseUrl.'/start/'.$trackId;
    }

    public function verifyCallback(array $query): ?Payment
    {
        $trackId = $query['trackId'] ?? null;
        $success = ($query['success'] ?? null) == '1';

        if (! $trackId || ! $success) {
            return null;
        }

        $payment = Payment::query()->where('gateway', 'zibal')->where('authority', (string) $trackId)->first();

        if (! $payment) {
            return null;
        }

        $baseUrl = rtrim(config('cms.zibal.base_url', 'https://gateway.zibal.ir'), '/');

        $response = Http::post($baseUrl.'/v1/verify', [
            'merchant' => config('cms.zibal.merchant'),
            'trackId' => $trackId,
        ]);

        $result = $response->json('result');

        if ($result === 100) {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'ref_id' => (string) $response->json('refNumber'),
                'gateway_response' => $response->json(),
            ]);

            $order = $payment->order;
            $order->update(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
            $this->orders->fulfill($order);

            return $payment;
        }

        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'gateway_response' => $response->json(),
        ]);
        $payment->order->update(['status' => Order::STATUS_FAILED]);

        return null;
    }
}
