<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZarinpalService
{
    public function __construct(private OrderService $orders) {}

    public function requestPayment(Order $order): string
    {
        if ($order->total <= 0) {
            $order->update(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
            $this->orders->fulfill($order);

            return route('checkout.success', $order);
        }

        $merchantId = config('cms.zarinpal.merchant_id');
        $sandbox = config('cms.zarinpal.sandbox', true);
        $baseUrl = $sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://api.zarinpal.com/pg/v4/payment';

        $response = Http::post($baseUrl.'/request.json', [
            'merchant_id' => $merchantId,
            'amount' => $order->total,
            'callback_url' => route('checkout.callback'),
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
            'gateway' => 'zarinpal',
            'authority' => $data['authority'],
            'amount' => $order->total,
            'status' => Payment::STATUS_PENDING,
            'gateway_response' => $response->json(),
        ]);

        $gatewayUrl = $sandbox
            ? 'https://sandbox.zarinpal.com/pg/StartPay/'
            : 'https://www.zarinpal.com/pg/StartPay/';

        return $gatewayUrl.$data['authority'];
    }

    public function verify(string $authority, int $amount): ?Payment
    {
        $payment = Payment::query()->where('authority', $authority)->first();

        if (! $payment) {
            return null;
        }

        $sandbox = config('cms.zarinpal.sandbox', true);
        $baseUrl = $sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://api.zarinpal.com/pg/v4/payment';

        $response = Http::post($baseUrl.'/verify.json', [
            'merchant_id' => config('cms.zarinpal.merchant_id'),
            'amount' => $amount,
            'authority' => $authority,
        ]);

        $code = $response->json('data.code');

        if ($code === 100 || $code === 101) {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'ref_id' => $response->json('data.ref_id'),
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
