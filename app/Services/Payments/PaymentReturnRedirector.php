<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;

class PaymentReturnRedirector
{
    public function afterCallback(?Order $order, bool $paid): RedirectResponse
    {
        if ($order?->channel === Order::CHANNEL_APP) {
            return redirect()->route('checkout.app-return', [
                'order' => $order->order_number,
                'status' => $paid ? 'success' : 'failed',
            ]);
        }

        if ($paid && $order) {
            return redirect()->route('checkout.success', $order);
        }

        return redirect()
            ->route('checkout.failed', array_filter(['order' => $order]))
            ->with('error', 'پرداخت ناموفق یا لغو شد. سبد خرید شما حفظ شده است.');
    }

    public function deepLink(string $orderNumber, string $status): ?string
    {
        $base = trim((string) config('cms.app.deep_link'));
        if ($base === '' || in_array($status, ['success', 'failed'], true) === false) {
            return null;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $base) !== 1) {
            return null;
        }

        $separator = str_contains($base, '?') ? '&' : '?';

        return $base.$separator.'order='.rawurlencode($orderNumber).'&status='.$status;
    }
}
