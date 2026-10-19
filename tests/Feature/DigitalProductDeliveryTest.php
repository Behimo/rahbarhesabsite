<?php

namespace Tests\Feature;

use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DigitalProductDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_digital_product_quantity_stays_one_when_added_twice(): void
    {
        $user = User::factory()->create();
        $product = ShopProduct::factory()->digital()->create([
            'price' => 50_000,
            'stock' => 10,
        ]);

        $this->actingAs($user)->post(route('cart.add', $product))->assertRedirect(route('cart.index'));
        $this->actingAs($user)->post(route('cart.add', $product))->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_digital_purchase_marks_order_paid_without_enrollment_or_license(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $product = ShopProduct::factory()->digital()->create([
            'price' => 75_000,
            'stock' => 10,
        ]);

        $this->actingAs($user)->post(route('cart.add', $product));

        Http::fake([
            'https://gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 910], 200),
            'https://gateway.zibal.ir/v1/verify' => Http::response([
                'result' => 100,
                'amount' => 750_000,
                'refNumber' => 'REF-DIGITAL',
            ], 200),
        ]);

        $this->actingAs($user)->post(route('checkout.process'));
        $order = Order::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('checkout.callback', ['gateway' => 'zibal', 'trackId' => 910, 'success' => 1]))
            ->assertRedirect(route('checkout.success', $order));

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame(0, CourseEnrollment::query()->count());
        Queue::assertNotPushed(IssueSpotplayerLicenseJob::class);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_owner_can_view_digital_checkout_success(): void
    {
        $user = User::factory()->create();
        $product = ShopProduct::factory()->digital()->create(['price' => 40_000]);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => Order::STATUS_PAID,
            'subtotal' => 40_000,
            'discount' => 0,
            'total' => 40_000,
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'shop_product_id' => $product->id,
            'title' => $product->title,
            'price' => 40_000,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_other_user_cannot_view_digital_checkout_success(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $owner->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => Order::STATUS_PAID,
            'subtotal' => 40_000,
            'discount' => 0,
            'total' => 40_000,
            'paid_at' => now(),
        ]);

        $this->actingAs($attacker)
            ->get(route('checkout.success', $order))
            ->assertForbidden();
    }
}
