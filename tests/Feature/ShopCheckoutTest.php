<?php

namespace Tests\Feature;

use App\Models\CmsAdmin;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopCheckoutTest extends TestCase
{
    use RefreshDatabase;

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
        $admin = CmsAdmin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_super' => true,
        ]);
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

        $this->actingAs($admin, 'cms')
            ->post(route('admin.orders.mark-paid', $order))
            ->assertRedirect();

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertTrue($user->fresh()->isEnrolledIn($product->course));
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
