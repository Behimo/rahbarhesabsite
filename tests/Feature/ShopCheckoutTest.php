<?php

namespace Tests\Feature;

use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\CmsSetting;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ShopCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.payment_gateway' => 'zibal']);
    }

    public function test_toman_converts_to_rials(): void
    {
        $this->assertSame(1_000_000, Money::tomanToRials(100_000));
    }

    public function test_checkout_keeps_cart_until_payment_succeeds(): void
    {
        $user = User::factory()->create();
        $product = $this->paidCourse();

        $this->actingAs($user)->post(route('cart.add', $product))->assertRedirect(route('cart.index'));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 555], 200),
        ]);

        $this->actingAs($user)
            ->post(route('checkout.process'))
            ->assertRedirect('https://gateway.zibal.ir/start/555');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING,
            'total' => 100000,
        ]);
    }

    public function test_payment_callback_is_idempotent_and_clears_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->paidCourse();

        $this->actingAs($user)->post(route('cart.add', $product));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 777], 200),
            'https://gateway.zibal.ir/v1/verify' => Http::response([
                'result' => 100,
                'amount' => 1_000_000,
                'refNumber' => 'REF-1',
                'cardNumber' => '6274-****-1234',
                'cardHash' => 'abc',
            ], 200),
        ]);

        $this->actingAs($user)->post(route('checkout.process'));
        $order = Order::query()->first();

        $this->actingAs($user)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => 777, 'success' => 1]))
            ->assertRedirect(route('checkout.success', $order));

        $this->actingAs($user)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => 777, 'success' => 1]))
            ->assertRedirect(route('checkout.success', $order));

        $this->assertSame(1, Order::query()->where('status', Order::STATUS_PAID)->count());
        $this->assertDatabaseMissing('cart_items', ['user_id' => $user->id]);
        $this->assertDatabaseHas('payments', [
            'authority' => '777',
            'status' => 'success',
            'card_pan' => '6274-****-1234',
            'user_id' => $user->id,
        ]);
        $this->assertTrue($user->fresh()->isEnrolledIn($product->course));
    }

    public function test_failed_payment_callback_redirects_to_failed_page(): void
    {
        $user = User::factory()->create();
        $product = $this->paidCourse();

        $this->actingAs($user)->post(route('cart.add', $product));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 888], 200),
        ]);

        $this->actingAs($user)->post(route('checkout.process'));
        $order = Order::query()->first();

        $this->actingAs($user)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => 888, 'success' => 0]))
            ->assertRedirect(route('checkout.failed', $order));

        $this->actingAs($user)
            ->get(route('checkout.failed', $order))
            ->assertOk()
            ->assertSee('پرداخت ناموفق', false);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
        ]);
        $this->assertSame(Order::STATUS_FAILED, $order->fresh()->status);
    }

    public function test_coupon_applies_on_checkout(): void
    {
        $user = User::factory()->create();
        $product = $this->paidCourse();
        Coupon::query()->create([
            'code' => 'OFF10',
            'type' => 'percentage_cart',
            'value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cart.add', $product));
        $this->actingAs($user)->post(route('cart.coupon'), ['code' => 'OFF10'])->assertSessionHas('success');

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 1], 200),
        ]);

        $this->actingAs($user)->post(route('checkout.process'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'coupon_code' => 'OFF10',
            'discount' => 10000,
            'total' => 90000,
        ]);
    }

    public function test_admin_can_mark_order_paid(): void
    {
        $admin = $this->makeAdmin(['email' => 'admin@test.com']);
        $user = User::factory()->create();
        $product = $this->paidCourse();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST',
            'status' => Order::STATUS_PENDING,
            'subtotal' => 100000,
            'discount' => 0,
            'total' => 100000,
        ]);
        $order->items()->create([
            'shop_product_id' => $product->id,
            'title' => $product->title,
            'price' => 100000,
            'quantity' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.mark-paid', $order))
            ->assertRedirect();

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertTrue($user->fresh()->isEnrolledIn($product->course));
    }

    public function test_checkout_ignores_a_gateway_the_client_tries_to_force(): void
    {
        $user = User::factory()->create();
        $product = $this->paidCourse();
        $this->actingAs($user)->post(route('cart.add', $product));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 555], 200),
        ]);

        $this->actingAs($user)
            ->post(route('checkout.process'), ['gateway' => 'zarinpal'])
            ->assertRedirect('https://gateway.zibal.ir/start/555');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'zarinpal'));
        $this->assertDatabaseHas('payments', [
            'gateway' => 'zibal',
            'authority' => '555',
        ]);
    }

    public function test_callback_rejects_a_mismatched_gateway_amount(): void
    {
        $user = User::factory()->create();
        $product = $this->paidCourse();
        $this->actingAs($user)->post(route('cart.add', $product));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 321], 200),
            'https://gateway.zibal.ir/v1/verify' => Http::response([
                'result' => 100,
                'amount' => 10,
                'refNumber' => 'REF-BAD',
            ], 200),
        ]);

        $this->actingAs($user)->post(route('checkout.process'));
        $order = Order::query()->first();

        $this->actingAs($user)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => 321, 'success' => 1]))
            ->assertRedirect(route('checkout.failed', $order));

        $this->assertSame(Order::STATUS_FAILED, $order->fresh()->status);
        $this->assertDatabaseHas('payments', [
            'authority' => '321',
            'status' => 'failed',
        ]);
        $this->assertFalse($user->fresh()->isEnrolledIn($product->course));
    }

    public function test_course_purchase_ignores_stock_and_queues_a_license(): void
    {
        $user = User::factory()->create();
        $product = $this->paidCourse();
        $product->update(['stock' => 1]);
        $product->course->update(['spotplayer_course_id' => 'course-spot-1']);
        Queue::fake();

        $this->actingAs($user)->post(route('cart.add', $product));
        $this->actingAs($user)->post(route('cart.add', $product));

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'quantity' => 1,
        ]);

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 901], 200),
            'https://gateway.zibal.ir/v1/verify' => Http::response([
                'result' => 100,
                'amount' => 1_000_000,
                'refNumber' => 'REF-COURSE',
            ], 200),
        ]);

        $this->actingAs($user)->post(route('checkout.process'));
        $order = Order::query()->first();

        $this->actingAs($user)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => 901, 'success' => 1]));

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertTrue($order->fresh()->isPaid());
        $this->assertTrue($user->fresh()->isEnrolledIn($product->course));
        Queue::assertPushed(IssueSpotplayerLicenseJob::class, function (IssueSpotplayerLicenseJob $job) use ($user, $product, $order) {
            return $job->userId === $user->id
                && $job->courseId === $product->course->id
                && $job->orderId === $order->id;
        });
    }

    public function test_physical_stock_is_reserved_until_payment_and_restored_on_failure(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $product = ShopProduct::query()->create([
            'slug' => 'reader-'.uniqid(),
            'title' => 'کارت‌خوان',
            'price' => 50000,
            'type' => ShopProduct::TYPE_PHYSICAL,
            'stock' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($first)->post(route('cart.add', $product));
        $this->actingAs($second)->post(route('cart.add', $product));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 902], 200),
        ]);

        $this->actingAs($first)->post(route('checkout.process'))
            ->assertRedirect('https://gateway.zibal.ir/start/902');

        $this->assertSame(0, $product->fresh()->stock);

        $this->actingAs($second)
            ->post(route('checkout.process'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame(1, Order::query()->count());

        $order = Order::query()->first();

        $this->actingAs($first)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => 902, 'success' => 0]))
            ->assertRedirect(route('checkout.failed', $order));

        $this->assertSame(Order::STATUS_FAILED, $order->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_unknown_callback_gateway_does_not_settle_the_payment(): void
    {
        $user = User::factory()->create();
        $product = $this->paidCourse();
        $this->actingAs($user)->post(route('cart.add', $product));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 444], 200),
        ]);

        $this->actingAs($user)->post(route('checkout.process'));
        $order = Order::query()->first();

        $this->actingAs($user)
            ->get(route('checkout.callback', [
                'gateway' => 'zarinpal',
                'trackId' => 444,
                'success' => 1,
                'Status' => 'OK',
                'Authority' => '444',
            ]))
            ->assertRedirect(route('checkout.failed'));

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'verify'));
    }

    public function test_admin_can_enable_one_gateway_and_checkout_uses_only_that(): void
    {
        config(['cms.zarinpal.sandbox' => true]);

        $admin = $this->makeAdmin(['email' => 'gateways@test.com']);

        $this->actingAs($admin)
            ->put(route('admin.settings.gateways'), [
                'gateways' => ['zibal' => '0', 'zarinpal' => '1'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(
            '{"zibal":false,"zarinpal":true}',
            CmsSetting::get('payment_gateways')
        );

        $user = User::factory()->create();
        $product = $this->paidCourse();
        $this->actingAs($user)->post(route('cart.add', $product));

        Http::fake([
            'https://sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => ['authority' => 'AUTH1', 'code' => 100],
            ], 200),
        ]);

        $this->actingAs($user)
            ->post(route('checkout.process'), ['gateway' => 'zibal'])
            ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/AUTH1');

        $this->assertDatabaseHas('payments', [
            'gateway' => 'zarinpal',
            'authority' => 'AUTH1',
        ]);
    }

    public function test_admin_cannot_disable_every_gateway(): void
    {
        $admin = $this->makeAdmin(['email' => 'gateways-off@test.com']);

        $this->actingAs($admin)
            ->put(route('admin.settings.gateways'), [
                'gateways' => ['zibal' => '0', 'zarinpal' => '0'],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull(CmsSetting::query()->where('key', 'payment_gateways')->first());
    }

    public function test_checkout_and_admin_settings_show_registered_gateways(): void
    {
        $user = User::factory()->create();
        $product = $this->paidCourse();
        $this->actingAs($user)->post(route('cart.add', $product));

        $this->actingAs($user)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('زیبال')
            ->assertDontSee('name="gateway"', false);

        $admin = $this->makeAdmin(['email' => 'gateways-ui@test.com']);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('درگاه‌های پرداخت')
            ->assertSee('زیبال')
            ->assertSee('زرین‌پال');

        $this->actingAs($admin)
            ->get(route('admin.gateways.index'))
            ->assertOk()
            ->assertSee('مرچنت')
            ->assertSee('درگاه فعال است')
            ->assertSee('name="gateways[zibal]"', false)
            ->assertSee('name="gateways[zarinpal]"', false)
            ->assertSee('border-inline-start', false);
    }

    public function test_admin_gateway_page_credentials_are_sent_to_the_driver(): void
    {
        $admin = $this->makeAdmin(['email' => 'gateways-cred@test.com']);

        $this->actingAs($admin)
            ->put(route('admin.gateways.update'), [
                'gateways' => ['zibal' => '1', 'zarinpal' => '0'],
                'credentials' => [
                    'zibal' => ['merchant' => 'panel-merchant', 'base_url' => 'https://gateway.zibal.ir'],
                    'zarinpal' => ['merchant_id' => 'panel-zarinpal', 'sandbox' => '1'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('cart.add', $this->paidCourse()));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 555], 200),
        ]);

        $this->actingAs($user)
            ->post(route('checkout.process'))
            ->assertRedirect('https://gateway.zibal.ir/start/555');

        Http::assertSent(fn ($request) => $request['merchant'] === 'panel-merchant');
    }

    private function paidCourse(): ShopProduct
    {
        $product = ShopProduct::query()->create([
            'slug' => 'paid-course-'.uniqid(),
            'title' => 'Paid Course',
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
