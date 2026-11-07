<?php

namespace App\Services\Orders;

use App\Exceptions\CouponUsageRejected;
use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\Order;
use App\Models\OrderPostPaymentTask;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/** Durable, at-least-once delivery. Consumers must tolerate duplicate dispatch. */
class PostPaymentProcessor
{
    public function __construct(
        private CouponService $coupons,
        private CartService $cart,
        private Dispatcher $bus,
    ) {}

    public function process(Order $order): void
    {
        if (! $order->isPaid()) {
            return;
        }

        $ids = OrderPostPaymentTask::query()->where('order_id', $order->id)
            ->whereNull('completed_at')->whereNull('rejected_at')->orderBy('id')->pluck('id');

        foreach ($ids as $id) {
            try {
                DB::transaction(function () use ($order, $id) {
                    $task = OrderPostPaymentTask::query()->lockForUpdate()->find($id);
                    if (! $task || $task->completed_at || $task->rejected_at) {
                        return;
                    }

                    $task->increment('attempts');
                    $task->last_attempt_at = now();

                    if ($task->type === OrderPostPaymentTask::TYPE_COUPON) {
                        try {
                            $this->coupons->recordUsage($order);
                        } catch (CouponUsageRejected $e) {
                            $task->forceFill(['rejected_at' => now(), 'last_error' => $e->getMessage()])->save();
                            $note = 'سقف کوپن بعد از پرداخت موفق رد شد و مصرف ثبت نشد.';
                            $existing = trim((string) $order->fresh()?->notes);
                            if (! str_contains($existing, $note)) {
                                $order->update(['notes' => trim($existing."\n".$note)]);
                            }
                            Log::warning('Paid order coupon task requires review.', ['order_id' => $order->id, 'task_id' => $task->id, 'error' => $e->getMessage()]);

                            return;
                        }
                    } elseif ($task->type === OrderPostPaymentTask::TYPE_CART_CLEAR) {
                        if ($order->user_id) {
                            $this->cart->clearProductsForUser((int) $order->user_id, array_map('intval', $task->payload['product_ids'] ?? []));
                        }
                    } elseif ($task->type === OrderPostPaymentTask::TYPE_LICENSE) {
                        $userId = (int) ($task->payload['user_id'] ?? 0);
                        $courseId = (int) ($task->payload['course_id'] ?? 0);
                        if ($userId < 1 || $courseId < 1) {
                            throw new RuntimeException('Invalid license task payload.');
                        }

                        // Send before acknowledging the outbox row. A crash after send may
                        // duplicate delivery, but can never silently lose the durable task.
                        $this->bus->dispatch((new IssueSpotplayerLicenseJob($userId, $courseId, $order->id))->beforeCommit());
                    } else {
                        throw new RuntimeException('Unknown post-payment task type.');
                    }

                    $task->forceFill(['completed_at' => now(), 'last_error' => null])->save();
                });
            } catch (Throwable $e) {
                OrderPostPaymentTask::query()->whereKey($id)->whereNull('completed_at')->update([
                    'attempts' => DB::raw('attempts + 1'),
                    'last_attempt_at' => now(),
                    'last_error' => mb_substr($e->getMessage(), 0, 4000),
                ]);
                Log::error('Post-payment task will be retried.', ['order_id' => $order->id, 'task_id' => $id, 'error' => $e->getMessage()]);
            }
        }
    }
}
