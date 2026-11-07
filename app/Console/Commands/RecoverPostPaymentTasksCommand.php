<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderPostPaymentTask;
use App\Services\Orders\PostPaymentProcessor;
use Illuminate\Console\Command;

class RecoverPostPaymentTasksCommand extends Command
{
    protected $signature = 'orders:recover-post-payment';

    protected $description = 'Retry open post-payment tasks for paid orders without requiring a gateway callback';

    public function handle(PostPaymentProcessor $processor): int
    {
        Order::query()->where('status', Order::STATUS_PAID)
            ->whereIn('id', OrderPostPaymentTask::query()->select('order_id')->whereNull('completed_at')->whereNull('rejected_at'))
            ->chunkById(100, function ($orders) use ($processor) {
                foreach ($orders as $order) {
                    $processor->process($order);
                }
            });

        return self::SUCCESS;
    }
}
