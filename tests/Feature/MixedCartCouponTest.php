<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Course;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MixedCartCouponTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.payment_gateway' => 'zibal']);
    }

    public function test_product_coupon_discounts_only_the_targeted_product(): void
    {
        $user = User::factory()->create();
        $targeted = $this->product(100_000);
        $other = $this->product(200_000);
        $coupon = $this->coupon('ITEM10', 10);
        $coupon->targets()->create([
            'target_type' => 'product',
            'target_id' => $targeted->id,
            'is_excluded' => false,
        ]);

        $this->addBoth($user, $targeted, $other);
        $this->actingAs($user)->post(route('cart.coupon'), ['code' => 'ITEM10'])->assertSessionHas('success');

        $this->checkout($user, 441);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'coupon_code' => 'ITEM10',
            'subtotal' => 300_000,
            'discount' => 10_000,
            'total' => 290_000,
        ]);
    }

    public function test_category_coupon_discounts_only_products_in_that_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->product()->create();
        $targeted = $this->product(100_000);
        $targeted->syncCategories([$category->id], $category->id);
        $other = $this->product(200_000);
        $coupon = $this->coupon('CAT10', 10);
        $coupon->targets()->create([
            'target_type' => 'category',
            'target_id' => $category->id,
            'is_excluded' => false,
        ]);

        $this->addBoth($user, $targeted, $other);
        $this->actingAs($user)->post(route('cart.coupon'), ['code' => 'CAT10'])->assertSessionHas('success');

        $this->checkout($user, 442);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'coupon_code' => 'CAT10',
            'subtotal' => 300_000,
            'discount' => 10_000,
            'total' => 290_000,
        ]);
    }

    public function test_sale_items_are_left_out_when_the_coupon_excludes_them(): void
    {
        $user = User::factory()->create();
        $regular = $this->product(100_000);
        $sale = $this->product(200_000, 150_000);
        $this->coupon('NOSALE', 10, ['exclude_sale_items' => true]);

        $this->addBoth($user, $regular, $sale);
        $this->actingAs($user)->post(route('cart.coupon'), ['code' => 'NOSALE'])->assertSessionHas('success');

        $this->checkout($user, 443);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'coupon_code' => 'NOSALE',
            'subtotal' => 250_000,
            'discount' => 10_000,
            'total' => 240_000,
        ]);
    }

    public function test_a_user_who_already_used_the_coupon_cannot_apply_it_again(): void
    {
        $spent = User::factory()->create();
        $fresh = User::factory()->create();
        $product = $this->product(100_000);
        $coupon = $this->coupon('ONCE', 10, ['usage_limit_per_user' => 1]);
        $order = Order::query()->create([
            'user_id' => $spent->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => Order::STATUS_PAID,
            'subtotal' => 100_000,
            'discount' => 10_000,
            'total' => 90_000,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ]);
        CouponUsage::query()->create([
            'coupon_id' => $coupon->id,
            'user_id' => $spent->id,
            'order_id' => $order->id,
            'discount_amount' => 10_000,
            'used_at' => now(),
        ]);
        $coupon->increment('used_count');

        $this->actingAs($spent)->post(route('cart.add', $product));
        $this->actingAs($spent)
            ->post(route('cart.coupon'), ['code' => 'ONCE'])
            ->assertSessionHas('error', 'کد تخفیف معتبر نیست.');

        $this->actingAs($fresh)->post(route('cart.add', $product));
        $this->actingAs($fresh)
            ->post(route('cart.coupon'), ['code' => 'ONCE'])
            ->assertSessionHas('success');
    }

    public function test_coupon_that_expires_after_checkout_does_not_void_a_captured_payment(): void
    {
        $user = User::factory()->create();
        $product = $this->paidCourse(100_000);
        $coupon = $this->coupon('FLASH', 10, [
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 444], 200),
            'https://gateway.zibal.ir/v1/verify' => Http::response([
                'result' => 100,
                'amount' => 900_000,
                'refNumber' => 'REF-EXPIRE',
            ], 200),
        ]);

        $this->actingAs($user)->post(route('cart.add', $product));
        $this->actingAs($user)->post(route('cart.coupon'), ['code' => 'FLASH'])->assertSessionHas('success');
        $this->actingAs($user)->post(route('checkout.process'));

        $coupon->update(['expires_at' => now()->subMinute()]);

        $order = Order::query()->where('user_id', $user->id)->firstOrFail();
        $this->actingAs($user)->get(route('checkout.callback', [
            'gateway' => 'zibal',
            'trackId' => 444,
            'success' => 1,
        ]))->assertRedirect(route('checkout.success', $order));

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame(90_000, (int) $order->total);
        $this->assertSame(10_000, (int) $order->discount);
        $this->assertSame(0, CouponUsage::query()->where('coupon_id', $coupon->id)->count());
        $this->assertStringContainsString('سقف کوپن', (string) $order->notes);
        $this->assertTrue($user->fresh()->isEnrolledIn($product->course));
    }

    private function coupon(string $code, int $percent, array $attributes = []): Coupon
    {
        return Coupon::query()->create(array_merge([
            'code' => $code,
            'type' => 'percentage_cart',
            'value' => $percent,
            'is_active' => true,
            'usage_limit_per_user' => 5,
        ], $attributes));
    }

    private function product(int $price, ?int $salePrice = null): ShopProduct
    {
        return ShopProduct::factory()->course()->create([
            'price' => $price,
            'sale_price' => $salePrice,
        ]);
    }

    private function paidCourse(int $price): ShopProduct
    {
        $product = $this->product($price);
        Course::factory()->create([
            'shop_product_id' => $product->id,
            'instructor_id' => null,
        ]);

        return $product->fresh('course');
    }

    private function addBoth(User $user, ShopProduct $first, ShopProduct $second): void
    {
        $this->actingAs($user)->post(route('cart.add', $first));
        $this->actingAs($user)->post(route('cart.add', $second));
    }

    private function checkout(User $user, int $trackId): void
    {
        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => $trackId], 200),
        ]);

        $this->actingAs($user)
            ->post(route('checkout.process'))
            ->assertRedirect('https://gateway.zibal.ir/start/'.$trackId);
    }
}
