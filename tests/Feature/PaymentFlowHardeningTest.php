<?php

namespace Tests\Feature;

use App\Models\CmsSetting;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Adversarial tests for money movement: every test encodes the invariant
 * "the amount charged / recorded / settled must match what the customer was
 * shown and what the gateway actually captured".
 *
 * A failing test here is a confirmed money-flow defect, not a flaky check.
 */
class PaymentFlowHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.payment_gateway' => 'zibal', 'cms.zarinpal.sandbox' => true]);
    }

    public function test_reused_pending_order_picks_up_a_coupon_added_after_checkout(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        Coupon::query()->create([
            'code' => 'OFF10',
            'type' => 'percentage_cart',
            'value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cart.add', $product));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 801], 200),
        ]);

        // First attempt: no coupon yet, user is redirected to the gateway and never returns.
        $this->actingAs($user)->post(route('checkout.process'));
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'total' => 100000, 'discount' => 0]);

        // User comes back, applies a 10% coupon, sees 90,000 on the checkout page.
        $this->actingAs($user)->post(route('cart.coupon'), ['code' => 'OFF10']);
        $this->actingAs($user)->get(route('checkout.index'))->assertSee(fa_digits(number_format(90000)));

        // Second attempt must charge what was displayed: 90,000.
        $this->actingAs($user)->post(route('checkout.process'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total' => 90000,
            'discount' => 10000,
        ]);
    }

    public function test_reused_pending_order_keeps_a_coupon_that_the_customer_removed(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        Coupon::query()->create([
            'code' => 'OFF10',
            'type' => 'percentage_cart',
            'value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cart.add', $product));
        $this->actingAs($user)->post(route('cart.coupon'), ['code' => 'OFF10']);

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 802], 200),
        ]);

        // First attempt with the coupon: pending order total 90,000, gateway asked for 900,000 rials.
        $this->actingAs($user)->post(route('checkout.process'));
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'total' => 90000]);

        // Customer removes the coupon and sees 100,000 on the checkout page.
        $this->actingAs($user)->delete(route('cart.coupon.remove'));
        $this->actingAs($user)->get(route('checkout.index'))->assertSee(fa_digits(number_format(100000)));

        // Second attempt must charge 100,000, not the stale discounted total.
        $this->actingAs($user)->post(route('checkout.process'));

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'total' => 100000, 'discount' => 0]);
    }

    public function test_success_callback_records_the_payment_that_was_actually_verified(): void
    {
        $this->enableBothGateways();
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->post(route('cart.add', $product));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 811], 200),
            'https://sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => ['authority' => 'AUTH-811', 'code' => 100],
            ], 200),
            'https://gateway.zibal.ir/v1/verify' => Http::response([
                'result' => 100,
                'amount' => 1000000,
                'refNumber' => 'REF-ZIBAL',
            ], 200),
        ]);

        // Two pending payments on the same order: zibal first, zarinpal second.
        $this->actingAs($user)->post(route('checkout.process'), ['gateway' => 'zibal']);
        $this->actingAs($user)->post(route('checkout.process'), ['gateway' => 'zarinpal']);

        $this->assertSame(2, Payment::query()->count());

        // Customer completes the zibal payment (the first one opened).
        $this->actingAs($user)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => 811, 'success' => 1]))
            ->assertRedirect(route('checkout.success', Order::query()->firstOrFail()));

        $order = Order::query()->firstOrFail();
        $this->assertTrue($order->fresh()->isPaid());

        $zibalPayment = Payment::query()->where('authority', '811')->firstOrFail();
        $zarinpalPayment = Payment::query()->where('authority', 'AUTH-811')->firstOrFail();

        // The money that changed hands was zibal's: its row must carry the success.
        $this->assertSame(Payment::STATUS_SUCCESS, $zibalPayment->status, 'Verified zibal payment was not recorded as success.');
        $this->assertSame('REF-ZIBAL', $zibalPayment->ref_id);
        // The zarinpal payment was never completed at the gateway.
        $this->assertSame(Payment::STATUS_PENDING, $zarinpalPayment->status, 'Unpaid zarinpal payment was written with the zibal success.');
        $this->assertNull($zarinpalPayment->ref_id);
    }

    public function test_second_gateway_payment_is_not_captured_after_order_is_paid(): void
    {
        $this->enableBothGateways();
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->post(route('cart.add', $product));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 821], 200),
            'https://sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => ['authority' => 'AUTH-821', 'code' => 100],
            ], 200),
            'https://sandbox.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => ['code' => 100, 'ref_id' => 'REF-ZARINPAL'],
            ], 200),
            'https://gateway.zibal.ir/v1/verify' => Http::response([
                'result' => 100,
                'amount' => 1000000,
                'refNumber' => 'REF-ZIBAL',
            ], 200),
        ]);

        $this->actingAs($user)->post(route('checkout.process'), ['gateway' => 'zibal']);
        $this->actingAs($user)->post(route('checkout.process'), ['gateway' => 'zarinpal']);

        // Customer pays with zarinpal first; the order becomes paid.
        $this->actingAs($user)->get(route('checkout.callback', [
            'gateway' => 'zarinpal',
            'Authority' => 'AUTH-821',
            'Status' => 'OK',
        ]));

        $order = Order::query()->firstOrFail();
        $this->assertTrue($order->fresh()->isPaid());

        // The customer then also completes the older zibal tab.
        $this->actingAs($user)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => 821, 'success' => 1]));

        // The gateway must never be asked to capture money for an order that is already paid.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'gateway.zibal.ir/v1/verify'));

        $zibalPayment = Payment::query()->where('authority', '821')->firstOrFail();
        $this->assertSame(Payment::STATUS_FAILED, $zibalPayment->status, 'Second capture was allowed on an already-paid order.');
        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_coupon_limit_hit_after_order_creation_does_not_void_a_captured_payment(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $product = $this->makeProduct();
        Coupon::query()->create([
            'code' => 'LIMIT1',
            'type' => 'percentage_cart',
            'value' => 10,
            'is_active' => true,
            'usage_limit_total' => 1,
        ]);

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::sequence()
                ->push(['result' => 100, 'trackId' => 901], 200)
                ->push(['result' => 100, 'trackId' => 902], 200),
            'https://gateway.zibal.ir/v1/verify' => Http::response([
                'result' => 100,
                'amount' => 900000,
                'refNumber' => 'REF-LIMIT',
            ], 200),
        ]);

        // Both customers create pending orders while the coupon still has budget.
        $this->actingAs($first)->post(route('cart.add', $product));
        $this->actingAs($first)->post(route('cart.coupon'), ['code' => 'LIMIT1']);
        $this->actingAs($first)->post(route('checkout.process'));

        $this->actingAs($second)->post(route('cart.add', $product));
        $this->actingAs($second)->post(route('cart.coupon'), ['code' => 'LIMIT1']);
        $this->actingAs($second)->post(route('checkout.process'));

        $this->assertSame(2, Order::query()->where('status', Order::STATUS_PENDING)->count());

        // First customer completes payment: coupon budget is now exhausted.
        $firstOrder = Order::query()->where('user_id', $first->id)->firstOrFail();
        $this->actingAs($first)->get(route('checkout.callback', [
            'gateway' => 'zibal',
            'trackId' => $firstOrder->payment->authority,
            'success' => 1,
        ]));
        $this->assertTrue($firstOrder->fresh()->isPaid());

        // Second customer completes payment: the gateway captures the money (verify=100).
        $secondOrder = Order::query()->where('user_id', $second->id)->firstOrFail();
        $this->actingAs($second)->get(route('checkout.callback', [
            'gateway' => 'zibal',
            'trackId' => $secondOrder->payment->authority,
            'success' => 1,
        ]));

        // Money was captured by the gateway, so the order must be paid and delivered.
        $secondOrder->refresh();
        $this->assertTrue(
            $secondOrder->isPaid(),
            'Gateway captured the payment (verify result 100) but the order was failed after coupon validation.'
        );
        $this->assertSame(Payment::STATUS_SUCCESS, $secondOrder->payment->status);
        $this->assertTrue($second->fresh()->isEnrolledIn($product->course));
    }

    public function test_stale_cancel_callback_of_an_old_payment_cannot_fail_a_new_payment_attempt(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->post(route('cart.add', $product));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::sequence()
                ->push(['result' => 100, 'trackId' => 951], 200)
                ->push(['result' => 100, 'trackId' => 952], 200),
            'https://gateway.zibal.ir/v1/verify' => Http::response([
                'result' => 100,
                'amount' => 1000000,
                'refNumber' => 'REF-RETRY',
            ], 200),
        ]);

        // First attempt is cancelled at the gateway.
        $this->actingAs($user)->post(route('checkout.process'));
        $order = Order::query()->firstOrFail();
        $firstAuthority = $order->payment->authority;

        $this->actingAs($user)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => $firstAuthority, 'success' => 0]))
            ->assertRedirect(route('checkout.failed', $order));
        $this->assertSame(Order::STATUS_FAILED, $order->fresh()->status);

        // Customer retries: a new payment is now open for the same order.
        $this->actingAs($user)->post(route('checkout.retry', $order));
        $order->refresh();
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $secondAuthority = $order->payments()->latest('id')->first()->authority;
        $this->assertNotSame($firstAuthority, $secondAuthority);

        // A replayed cancel callback for the OLD payment arrives (browser back/refresh).
        $this->actingAs($user)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => $firstAuthority, 'success' => 0]));

        // The active payment attempt must not be failed by the stale one.
        $this->assertSame(
            Order::STATUS_PENDING,
            $order->fresh()->status,
            'A stale cancel callback for an old failed payment failed the order that is being paid right now.'
        );

        // And the customer must still be able to complete the active attempt.
        $this->actingAs($user)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => $secondAuthority, 'success' => 1]))
            ->assertRedirect(route('checkout.success', $order));
        $this->assertTrue($order->fresh()->isPaid());
    }

    private function enableBothGateways(): void
    {
        CmsSetting::set('payment_gateways', json_encode(['zibal' => true, 'zarinpal' => true]));
    }

    private function makeProduct(): ShopProduct
    {
        $product = ShopProduct::query()->create([
            'slug' => 'hardening-'.uniqid(),
            'title' => 'Hardening Course',
            'price' => 100000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);

        Course::query()->create([
            'shop_product_id' => $product->id,
            'level' => 'beginner',
        ]);

        return $product->fresh('course');
    }
}
