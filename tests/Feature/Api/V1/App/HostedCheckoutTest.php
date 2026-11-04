<?php

namespace Tests\Feature\Api\V1\App;

use App\Models\AppCheckoutSession;
use App\Models\Course;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class HostedCheckoutTest extends MobileApiTestCase
{
    private function purchase(?User $user = null, array $input = []): array
    {
        $user ??= User::factory()->create();
        $product = ShopProduct::factory()->course()->create(['price' => 150000, 'sale_price' => null]);
        Course::factory()->create(['shop_product_id' => $product->id, 'instructor_id' => null]);
        $headers = $this->appHeaders($user) + ['Idempotency-Key' => 'hosted-purchase-key'];
        $response = $this->withHeaders($headers)->postJson('/api/v1/app/orders', $input + ['product_id' => $product->id])->assertCreated();

        return [$response->json('data.checkout_url'), $user, $product, $headers];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();
    }

    public function test_browser_starts_payment_for_app_owner_without_logging_into_the_website(): void
    {
        Http::fake(['https://gateway.zibal.ir/*' => Http::response(['result' => 100, 'trackId' => 456])]);
        [$url, $owner, $product, $headers] = $this->purchase();
        $this->assertSame(0, Order::query()->count());
        Http::assertNothingSent();
        $this->assertStringNotContainsString($headers['Authorization'], $url);

        $browserUser = User::factory()->create();
        auth('web')->login($browserUser);
        $this->get($url)->assertRedirect('https://gateway.zibal.ir/start/456')->assertHeader('Referrer-Policy', 'no-referrer');
        $order = Order::query()->sole();
        $this->assertSame($owner->id, $order->user_id);
        $this->assertSame(Order::CHANNEL_APP, $order->channel);
        $this->assertAuthenticatedAs($browserUser, 'web');
        $this->get($url)->assertRedirect('https://gateway.zibal.ir/start/456');
        $this->assertSame(1, Order::query()->count());
        Http::assertSentCount(1);
    }

    public function test_invalid_or_expired_links_do_not_create_orders(): void
    {
        [$url] = $this->purchase();
        $this->get($url.'&product_id=99')->assertForbidden();
        $this->travel(11)->minutes();
        $this->get($url)->assertForbidden();
        $this->assertSame(0, Order::query()->count());
        Http::assertNothingSent();
    }

    public function test_head_preview_does_not_start_a_purchase(): void
    {
        [$url] = $this->purchase();
        $this->head($url)->assertOk();
        $this->assertSame(0, Order::query()->count());
        Http::assertNothingSent();
    }

    public function test_revoking_the_app_token_disables_its_purchase_link(): void
    {
        [$url, $user] = $this->purchase();
        $user->tokens()->delete();
        $this->get($url)->assertForbidden();
        $this->assertSame(0, Order::query()->count());
        Http::assertNothingSent();
    }

    public function test_blocked_accounts_cannot_use_an_issued_link(): void
    {
        [$url, $user] = $this->purchase();
        $user->update(['status' => User::STATUS_BANNED]);
        $this->get($url)->assertForbidden();
        $this->assertSame(0, Order::query()->count());
        Http::assertNothingSent();
    }

    public function test_site_rechecks_product_availability(): void
    {
        [$url, $user, $product] = $this->purchase();
        $product->update(['is_published' => false]);
        $this->get($url)->assertStatus(422);
        $this->assertSame(0, Order::query()->count());
        Http::assertNothingSent();
    }

    public function test_an_expired_link_can_be_renewed_from_the_authenticated_app(): void
    {
        [$url, $user, $product, $headers] = $this->purchase();
        $this->travel(11)->minutes();
        $response = $this->withHeaders($headers)->postJson('/api/v1/app/orders', ['product_id' => $product->id])->assertOk();
        $this->assertNotSame($url, $response->json('data.checkout_url'));
        $this->assertSame(1, AppCheckoutSession::query()->count());
        $this->get($url)->assertForbidden();
    }

    public function test_coupon_errors_are_shown_on_the_site_before_payment(): void
    {
        [$url] = $this->purchase(input: ['coupon_code' => 'DOES-NOT-EXIST']);
        $this->get($url)->assertStatus(422)->assertSee('خرید تکمیل نشد');
        $this->assertSame(0, Order::query()->count());
        Http::assertNothingSent();
    }

    public function test_gateway_failure_can_be_retried_without_another_order(): void
    {
        Http::fake(['https://gateway.zibal.ir/*' => Http::sequence()
            ->push(['result' => 500], 503)
            ->push(['result' => 100, 'trackId' => 789])]);
        [$url] = $this->purchase();
        $this->get($url)->assertStatus(422)->assertSee('خرید تکمیل نشد');
        $this->get($url)->assertRedirect('https://gateway.zibal.ir/start/789');
        $this->assertSame(1, Order::query()->count());
    }

    public function test_full_discount_completes_on_the_site_without_requiring_a_gateway(): void
    {
        \App\Models\Coupon::query()->create([
            'code' => 'FREE100', 'type' => 'percentage_cart', 'value' => 100, 'is_active' => true,
        ]);
        $this->mock(\App\Services\PaymentGatewayManager::class, function ($mock) {
            $mock->shouldNotReceive('resolveForCheckout');
            $mock->shouldNotReceive('requestPayment');
        });
        [$url, $user, $product] = $this->purchase(input: ['coupon_code' => 'FREE100']);
        $this->get($url)->assertRedirect(route('checkout.app-return', [
            'order' => $user->orders()->sole()->order_number, 'status' => 'success',
        ]));
        $this->assertSame(Order::STATUS_PAID, $user->orders()->sole()->status);
        $this->assertTrue($user->fresh()->isEnrolledIn($product->course));
        $this->assertGuest('web');
        Http::assertNothingSent();
    }

    public function test_expired_app_token_cannot_start_payment(): void
    {
        [$url, $user] = $this->purchase();
        $user->tokens()->update(['expires_at' => now()->subMinute()]);
        $this->get($url)->assertForbidden();
        $this->assertSame(0, Order::query()->count());
        Http::assertNothingSent();
    }

    public function test_site_uses_current_server_price_not_link_or_client_values(): void
    {
        Http::fake(['https://gateway.zibal.ir/*' => Http::response(['result' => 100, 'trackId' => 987])]);
        [$url, $user, $product] = $this->purchase(input: ['price' => 1]);
        $product->update(['price' => 170000]);
        $this->get($url)->assertRedirect('https://gateway.zibal.ir/start/987');
        $this->assertSame(170000, $user->orders()->sole()->total);
        Http::assertSent(fn ($request) => $request['amount'] === 1700000);
    }
}
