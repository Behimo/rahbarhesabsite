<?php

namespace Tests\Feature;

use App\Exceptions\CouponUsageRejected;
use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\Order;
use App\Models\OrderPostPaymentTask;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class PostPaymentWorkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_recovers_tasks_left_after_payment_commit_without_another_callback(): void
    {
        Queue::fake();
        $task = $this->licenseTask();

        $this->artisan('orders:recover-post-payment')->assertSuccessful();
        $this->assertNotNull($task->fresh()->completed_at);
        Queue::assertPushed(IssueSpotplayerLicenseJob::class, 1);

        $this->artisan('orders:recover-post-payment')->assertSuccessful();
        Queue::assertPushed(IssueSpotplayerLicenseJob::class, 1);
    }

    public function test_failed_queue_send_remains_open_and_the_command_can_retry_it(): void
    {
        $task = $this->licenseTask();
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('Queue unavailable'));
        $dispatcher->shouldReceive('dispatch')->once()->andReturn('accepted-job-id');
        $this->app->instance(Dispatcher::class, $dispatcher);

        $this->artisan('orders:recover-post-payment')->assertSuccessful();
        $this->assertNull($task->fresh()->completed_at);
        $this->assertSame('Queue unavailable', $task->fresh()->last_error);
        $this->assertSame(1, $task->fresh()->attempts);

        $this->artisan('orders:recover-post-payment')->assertSuccessful();
        $this->assertNotNull($task->fresh()->completed_at);
        $this->assertNull($task->fresh()->last_error);
        $this->assertSame(2, $task->fresh()->attempts);
    }

    public function test_coupon_rejection_is_recorded_for_review_not_as_success_and_other_tasks_continue(): void
    {
        Queue::fake();
        $license = $this->licenseTask();
        $couponTask = OrderPostPaymentTask::query()->create([
            'order_id' => $license->order_id,
            'task_key' => 'coupon',
            'type' => OrderPostPaymentTask::TYPE_COUPON,
            'payload' => [],
        ]);
        $coupons = Mockery::mock(CouponService::class);
        $coupons->shouldReceive('recordUsage')->once()->andThrow(new CouponUsageRejected('Coupon rejected'));
        $this->app->instance(CouponService::class, $coupons);

        $this->artisan('orders:recover-post-payment')->assertSuccessful();
        $this->assertNull($couponTask->fresh()->completed_at);
        $this->assertNotNull($couponTask->fresh()->rejected_at);
        $this->assertSame('Coupon rejected', $couponTask->fresh()->last_error);
        $this->assertNotNull($license->fresh()->completed_at);
        $this->artisan('orders:recover-post-payment')->assertSuccessful();
    }

    public function test_pending_orders_are_not_delivered_by_recovery_worker(): void
    {
        Queue::fake();
        $task = $this->licenseTask();
        $task->order->update(['status' => Order::STATUS_PENDING]);
        $this->artisan('orders:recover-post-payment')->assertSuccessful();
        $this->assertNull($task->fresh()->completed_at);
        Queue::assertNothingPushed();
    }

    public function test_transient_coupon_failure_stays_retryable_and_does_not_block_cart_cleanup(): void
    {
        Queue::fake();
        $license = $this->licenseTask();
        $coupon = OrderPostPaymentTask::query()->create([
            'order_id' => $license->order_id, 'task_key' => 'coupon',
            'type' => OrderPostPaymentTask::TYPE_COUPON, 'payload' => [],
        ]);
        $cart = OrderPostPaymentTask::query()->create([
            'order_id' => $license->order_id, 'task_key' => 'cart_clear',
            'type' => OrderPostPaymentTask::TYPE_CART_CLEAR, 'payload' => ['product_ids' => []],
        ]);
        $coupons = Mockery::mock(CouponService::class);
        $coupons->shouldReceive('recordUsage')->once()->andThrow(new \RuntimeException('Temporary storage failure'));
        $coupons->shouldReceive('recordUsage')->once()->andReturnNull();
        $this->app->instance(CouponService::class, $coupons);

        $this->artisan('orders:recover-post-payment')->assertSuccessful();
        $this->assertNull($coupon->fresh()->completed_at);
        $this->assertNull($coupon->fresh()->rejected_at);
        $this->assertNotNull($cart->fresh()->completed_at);
        $this->artisan('orders:recover-post-payment')->assertSuccessful();
        $this->assertNotNull($coupon->fresh()->completed_at);
        $this->assertSame(2, $coupon->fresh()->attempts);
    }

    private function licenseTask(): OrderPostPaymentTask
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
            'subtotal' => 100000,
            'discount' => 0,
            'total' => 100000,
        ]);

        return OrderPostPaymentTask::query()->create([
            'order_id' => $order->id,
            'task_key' => 'license:1',
            'type' => OrderPostPaymentTask::TYPE_LICENSE,
            'payload' => ['user_id' => $user->id, 'course_id' => 1],
        ]);
    }
}
