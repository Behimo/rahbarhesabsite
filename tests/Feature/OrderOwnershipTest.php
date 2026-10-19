<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_users_checkout_success(): void
    {
        [$ownerOrder, $attacker] = $this->paidOrderOwnedByAnotherUser();

        $this->actingAs($attacker)
            ->get(route('checkout.success', $ownerOrder))
            ->assertForbidden();
    }

    public function test_user_cannot_retry_another_users_payment(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $order = $this->createOrder($owner, Order::STATUS_FAILED, 150_000);

        $this->actingAs($attacker)
            ->post(route('checkout.retry', $order))
            ->assertForbidden();
    }

    public function test_user_cannot_view_another_users_checkout_failed_page(): void
    {
        [$ownerOrder, $attacker] = $this->paidOrderOwnedByAnotherUser();

        $this->actingAs($attacker)
            ->get(route('checkout.failed', $ownerOrder))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_checkout_success(): void
    {
        $owner = User::factory()->create();
        $order = $this->createOrder($owner, Order::STATUS_PAID, 100_000);

        $this->get(route('checkout.success', $order))
            ->assertRedirect(route('login'));
    }

    public function test_guest_failed_page_does_not_leak_order_details(): void
    {
        $owner = User::factory()->create();
        $order = $this->createOrder($owner, Order::STATUS_FAILED, 100_000);

        $this->get(route('checkout.failed', $order))
            ->assertOk()
            ->assertDontSee($order->order_number);
    }

    public function test_owner_can_view_paid_checkout_success(): void
    {
        $owner = User::factory()->create();
        $order = $this->createOrder($owner, Order::STATUS_PAID, 100_000);

        $this->actingAs($owner)
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_panel_orders_only_lists_current_users_orders(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ownOrder = $this->createOrder($owner, Order::STATUS_PAID, 80_000);
        $foreignOrder = $this->createOrder($other, Order::STATUS_PAID, 90_000);

        $this->actingAs($owner)
            ->get(route('panel.orders'))
            ->assertOk()
            ->assertSee($ownOrder->order_number)
            ->assertDontSee($foreignOrder->order_number);
    }

    /** @return array{0: Order, 1: User} */
    private function paidOrderOwnedByAnotherUser(): array
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $order = $this->createOrder($owner, Order::STATUS_PAID, 100_000);

        return [$order, $attacker];
    }

    private function createOrder(User $user, string $status, int $total): Order
    {
        return Order::query()->create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => $status,
            'subtotal' => $total,
            'discount' => 0,
            'total' => $total,
            'currency' => 'IRT',
            'paid_at' => $status === Order::STATUS_PAID ? now() : null,
        ]);
    }
}
