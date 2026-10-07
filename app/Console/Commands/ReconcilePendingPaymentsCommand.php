<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymentGatewayRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePendingPaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile {--minutes=30 : پرداخت‌های pending قدیمی‌تر از این مدت}';

    protected $description = 'تسویه یا بستن پرداخت‌های pending که کال‌بک مرورگر را از دست داده‌اند';

    public function handle(PaymentGatewayRegistry $gateways): int
    {
        $minutes = max(5, (int) $this->option('minutes'));
        $cutoff = now()->subMinutes($minutes);

        $payments = Payment::query()
            ->with('order')
            ->where('status', Payment::STATUS_PENDING)
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id')
            ->get();

        foreach ($payments as $payment) {
            $payment->refresh();

            if ($payment->status !== Payment::STATUS_PENDING) {
                continue;
            }

            if (! $gateways->has($payment->gateway)) {
                Log::warning('Pending payment has no registered gateway.', [
                    'payment_id' => $payment->id,
                    'gateway' => $payment->gateway,
                ]);

                continue;
            }

            $gateways->make($payment->gateway)->reconcile($payment);
        }

        $this->info($payments->count().' پرداخت pending بررسی شد.');

        return self::SUCCESS;
    }
}
