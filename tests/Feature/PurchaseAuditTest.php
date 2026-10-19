<?php

namespace Tests\Feature;

use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\CartItem;
use App\Models\CmsSetting;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\SpotplayerLicense;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\SpotPlayerService;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Purchase audit specifications. Assertions describe the required safe behavior,
 * not the current implementation. Run only against an in-memory test database.
 */
class PurchaseAuditTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as private refreshIsolatedDatabase;
    }

    public function refreshDatabase(): void
    {
        // Check isolation before RefreshDatabase can execute any migrations.
        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertEmpty(config('database.connections.sqlite.url'));

        Http::preventStrayRequests();
        $this->refreshIsolatedDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cms.payment_gateway' => 'zibal',
            'cms.zibal.base_url' => 'https://gateway.zibal.ir',
            'cms.zibal.merchant' => 'zibal',
            'cms.zarinpal.sandbox' => true,
            'cms.zarinpal.merchant_id' => 'audit-test-merchant',
            'cms.spotplayer.api_key' => 'audit-test-key',
            'cms.spotplayer.base_url' => 'https://spotplayer.test',
            'cache.default' => 'array',
            'mail.default' => 'array',
        ]);
        Queue::fake();
        Http::preventStrayRequests();
    }

    public function test_first_purchase_coupon_usage_is_recorded_after_payment(): void
    {
        [$user, $product] = $this->cartWithCourse();
        $coupon = $this->coupon(['is_first_order_only' => true, 'usage_limit_total' => 1]);
        $this->post(route('cart.coupon'), ['code' => $coupon->code])->assertSessionHas('success');
        $this->fakeZibal(900_000);

        $this->post(route('checkout.process'))->assertRedirect('https://gateway.zibal.ir/start/701');
        $order = Order::query()->sole();
        $this->get($this->checkoutCallbackUrl())->assertRedirect(route('checkout.success', $order));

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertTrue($user->fresh()->isEnrolledIn($product->course));
        $this->assertDatabaseHas('coupon_usages', ['coupon_id' => $coupon->id, 'order_id' => $order->id]);
        $this->assertSame(1, (int) $coupon->fresh()->used_count);

        // Idempotent re-callback must not double-count usage.
        $this->get($this->checkoutCallbackUrl())->assertRedirect(route('checkout.success', $order));
        $this->assertSame(1, (int) $coupon->fresh()->used_count);
        $this->assertSame(1, CouponUsage::query()->where('coupon_id', $coupon->id)->count());
    }

    public function test_retry_does_not_request_a_new_payment_using_an_expired_coupon(): void
    {
        $this->cartWithCourse();
        $coupon = $this->coupon(['expires_at' => now()->addHour()]);
        $this->post(route('cart.coupon'), ['code' => $coupon->code])->assertSessionHas('success');
        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::sequence()
                ->push(['result' => 100, 'trackId' => 701])
                ->push(['result' => 100, 'trackId' => 702]),
        ]);
        $this->post(route('checkout.process'))->assertRedirect('https://gateway.zibal.ir/start/701');
        $order = Order::query()->sole();
        $this->get($this->checkoutCallbackUrl(false))->assertRedirect(route('checkout.failed', $order));
        $coupon->update(['expires_at' => now()->subMinute()]);

        $this->post(route('checkout.retry', $order))->assertRedirect(route('cart.index'));

        // Either reject the expired quote or requote; never charge its old discount again.
        $requests = Http::recorded(fn ($request) => $request->url() === 'https://gateway.zibal.ir/v1/request');
        $this->assertFalse($requests->skip(1)->contains(
            fn (array $pair) => (int) $pair[0]['amount'] === 900_000
        ));
    }

    public function test_a_second_gateway_request_failure_preserves_the_first_attempt(): void
    {
        $this->cartWithCourse();
        CmsSetting::set('payment_gateways', json_encode(['zibal' => true, 'zarinpal' => true]));
        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 701]),
            'https://sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'errors' => ['code' => -9, 'message' => 'Temporary failure'],
            ], 503),
        ]);

        $this->post(route('checkout.process'), ['gateway' => 'zibal'])
            ->assertRedirect('https://gateway.zibal.ir/start/701');
        $payment = Payment::query()->sole();
        $this->post(route('checkout.process'), ['gateway' => 'zarinpal'])->assertRedirect();

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $payment->order->fresh()->status);
    }

    public function test_a_transient_verify_error_can_be_recovered_by_reconciliation(): void
    {
        $this->cartWithCourse();
        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 701]),
            'https://gateway.zibal.ir/v1/verify' => Http::sequence()
                ->push('Temporary outage', 503)
                ->push(['result' => 100, 'amount' => 1_000_000, 'refNumber' => 'AUDIT-RECOVERED']),
        ]);
        $this->post(route('checkout.process'))->assertRedirect('https://gateway.zibal.ir/start/701');
        $this->get($this->checkoutCallbackUrl());
        $payment = Payment::query()->sole();
        $payment->forceFill(['created_at' => now()->subMinutes(31)])->save();

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertTrue($payment->order->fresh()->isPaid());
    }

    public function test_an_unpaid_order_does_not_render_a_success_receipt(): void
    {
        [$user] = $this->cartWithCourse();
        $order = app(OrderService::class)->createFromCart($user);

        $response = $this->get(route('checkout.success', $order));

        $response->assertRedirect(route('checkout.failed', $order));
        $response->assertDontSee('سفارش با موفقیت تکمیل شد');
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_a_reused_pending_order_cannot_start_payment_for_an_unpublished_product(): void
    {
        [$user, $product] = $this->cartWithCourse();
        app(OrderService::class)->createFromCart($user);
        $product->update(['is_published' => false]);
        $this->fakeZibal();

        $this->post(route('checkout.process'));

        Http::assertNothingSent();
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_physical_products_without_inventory_tracking_can_have_multiple_units(): void
    {
        $user = User::factory()->create();
        $product = ShopProduct::query()->create([
            'slug' => 'audit-untracked-physical', 'title' => 'Audit physical product',
            'type' => ShopProduct::TYPE_PHYSICAL, 'price' => 100_000,
            'stock' => null, 'is_published' => true,
        ]);
        $this->actingAs($user)->post(route('cart.add', $product))->assertRedirect();
        $item = CartItem::query()->sole();

        $this->put(route('cart.update', $item), ['quantity' => 3])->assertRedirect();

        $this->assertSame(3, (int) $item->fresh()->quantity);
    }

    public function test_deleting_a_purchased_course_preserves_the_order_line(): void
    {
        [$user, $product] = $this->cartWithCourse();
        $order = app(OrderService::class)->createFromCart($user);
        app(OrderService::class)->markPaid($order);
        $item = $order->items()->sole();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->delete(route('admin.courses.destroy', $product));

        $this->assertSoftDeleted('shop_products', ['id' => $product->id]);
        $this->assertDatabaseHas('order_items', [
            'id' => $item->id, 'order_id' => $order->id, 'title' => $product->title,
            'shop_product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_PAID]);
    }

    public function test_deleting_a_customer_preserves_orders_and_payments(): void
    {
        [$user, $product] = $this->cartWithCourse();
        $order = app(OrderService::class)->createFromCart($user);
        app(OrderService::class)->markPaid($order, [
            'status' => Payment::STATUS_SUCCESS,
            'gateway' => 'manual',
            'amount' => $order->total,
        ], Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'manual',
            'amount' => $order->total,
            'status' => Payment::STATUS_PENDING,
        ]));
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'title' => $product->title]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id]);
    }

    public function test_order_amounts_use_store_currency_while_gateways_receive_rials(): void
    {
        [$user] = $this->cartWithCourse();
        $this->fakeZibal(1_000_000);

        $this->post(route('checkout.process'))->assertRedirect('https://gateway.zibal.ir/start/701');
        $order = Order::query()->sole();

        $this->assertSame(Money::STORE_CURRENCY, $order->currency);
        $this->assertSame(100_000, (int) $order->total);
        Http::assertSent(fn ($request) => $request->url() === 'https://gateway.zibal.ir/v1/request'
            && (int) $request['amount'] === 1_000_000);
    }

    public function test_duplicate_gateway_authority_is_rejected(): void
    {
        [$user] = $this->cartWithCourse();
        $order = app(OrderService::class)->createFromCart($user);
        Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'zibal',
            'authority' => 'DUP-AUTH',
            'amount' => $order->total,
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->expectException(QueryException::class);

        Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'zibal',
            'authority' => 'DUP-AUTH',
            'amount' => $order->total,
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_concurrent_style_cart_adds_keep_a_single_line(): void
    {
        $user = User::factory()->create();
        $product = ShopProduct::query()->create([
            'slug' => 'audit-cart-unique', 'title' => 'Audit cart product',
            'type' => ShopProduct::TYPE_PHYSICAL, 'price' => 50_000,
            'stock' => 10, 'is_published' => true,
        ]);
        $this->actingAs($user);
        $cart = app(CartService::class);

        $cart->add($product, 1);
        $cart->add($product, 2);

        $this->assertSame(1, CartItem::query()->where('user_id', $user->id)->count());
        $this->assertSame(3, (int) CartItem::query()->where('user_id', $user->id)->value('quantity'));
    }

    public function test_a_queued_license_job_cannot_issue_after_enrollment_is_revoked(): void
    {
        Http::preventStrayRequests();
        [$user, $product] = $this->cartWithCourse();
        $course = $product->course;
        $course->update(['spotplayer_course_id' => 'audit-course']);
        $enrollment = app(OrderService::class)->enrollUser($user, $course);
        app(OrderService::class)->revokeEnrollment($enrollment);
        $this->assertSame(CourseEnrollment::STATUS_REVOKED, $enrollment->fresh()->status);
        Http::fake([
            'https://spotplayer.test/licenses' => Http::response([
                '_id' => 'audit-license', 'license_key' => 'AUDIT-KEY',
            ]),
        ]);

        (new IssueSpotplayerLicenseJob($user->id, $course->id))->handle(app(SpotPlayerService::class));

        Http::assertNothingSent();
        $this->assertDatabaseMissing('spotplayer_licenses', [
            'user_id' => $user->id, 'course_id' => $course->id,
            'status' => SpotplayerLicense::STATUS_ISSUED,
        ]);
    }

    public function test_failed_remote_revocation_is_not_recorded_as_successful(): void
    {
        Http::preventStrayRequests();
        [$user, $product] = $this->cartWithCourse();
        $license = SpotplayerLicense::query()->create([
            'user_id' => $user->id, 'course_id' => $product->course->id,
            'spot_license_id' => 'audit-license', 'license_key' => 'AUDIT-KEY',
            'status' => SpotplayerLicense::STATUS_ISSUED,
        ]);
        Http::fake([
            'https://spotplayer.test/licenses/audit-license' => Http::response(['error' => 'Unavailable'], 503),
        ]);

        try {
            app(SpotPlayerService::class)->revokeLicense($license);
        } catch (\RuntimeException) {
            // Reporting a failure is acceptable; claiming a successful revocation is not.
        }

        Http::assertSentCount(1);
        $this->assertNotSame(SpotplayerLicense::STATUS_REVOKED, $license->fresh()->status);
    }

    /** @return array{User, ShopProduct} */
    private function cartWithCourse(): array
    {
        $user = User::factory()->create();
        $product = ShopProduct::query()->create([
            'slug' => 'audit-course', 'title' => 'Audit course', 'price' => 100_000,
            'type' => ShopProduct::TYPE_COURSE, 'is_published' => true,
        ]);
        Course::query()->create(['shop_product_id' => $product->id, 'level' => 'beginner']);
        $this->actingAs($user)->post(route('cart.add', $product))->assertRedirect(route('cart.index'));

        return [$user, $product->fresh('course')];
    }

    private function coupon(array $attributes = []): Coupon
    {
        return Coupon::query()->create(array_merge([
            'code' => 'AUDIT10', 'type' => 'percentage_cart', 'value' => 10, 'is_active' => true,
        ], $attributes));
    }

    private function fakeZibal(int $amount = 1_000_000): void
    {
        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 701]),
            'https://gateway.zibal.ir/v1/verify' => Http::response([
                'result' => 100, 'amount' => $amount, 'refNumber' => 'AUDIT-REF',
            ]),
        ]);
    }

    private function checkoutCallbackUrl(bool $success = true): string
    {
        return route('checkout.callback', ['gateway' => 'zibal', 'trackId' => 701, 'success' => (int) $success]);
    }
}
