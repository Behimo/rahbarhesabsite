<?php

namespace Tests\Feature\Concurrency;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Concurrency;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Row-lock / race invariants that SQLite :memory: does not reliably prove.
 * Run with a disposable MySQL database:
 *   DB_CONNECTION=mysql DB_DATABASE=rahbarhesab_test php artisan test --group=mysql
 *
 * Never point these at the production database.
 */
#[Group('mysql')]
class PaymentCouponStockRaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Disable per-test transactions so parallel child processes can see committed fixtures.
     *
     * @var list<string>
     */
    protected $connectionsToTransact = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('MySQL concurrency suite requires DB_CONNECTION=mysql on a disposable empty database.');
        }
    }

    public function test_coupon_usage_limit_total_allows_only_one_recorded_usage(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'ONCEONLY',
            'type' => 'percentage_cart',
            'value' => 10,
            'is_active' => true,
            'usage_limit_total' => 1,
            'used_count' => 0,
        ]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $product = $this->courseProduct(100_000);

        $orderA = $this->pendingDiscountedOrder($userA, $product, $coupon);
        $orderB = $this->pendingDiscountedOrder($userB, $product, $coupon);

        $orders = app(OrderService::class);
        $orders->markPaid($orderA);
        $orders->markPaid($orderB);

        $this->assertSame(1, CouponUsage::query()->where('coupon_id', $coupon->id)->count());
        $this->assertSame(1, (int) $coupon->fresh()->used_count);
        $this->assertTrue($orderA->fresh()->isPaid());
        $this->assertTrue($orderB->fresh()->isPaid());
        $this->assertStringContainsString(
            'سقف کوپن',
            (string) $orderB->fresh()->notes
        );
    }

    public function test_physical_stock_cannot_be_over_reserved_by_two_checkouts(): void
    {
        $product = ShopProduct::factory()->physical()->create([
            'price' => 80_000,
            'stock' => 1,
            'is_published' => true,
        ]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->addToCart($userA, $product);
        $this->addToCart($userB, $product);

        $orders = app(OrderService::class);
        $first = $orders->createFromCart($userA);
        $this->assertSame(Order::STATUS_PENDING, $first->status);
        $this->assertSame(0, (int) $product->fresh()->stock);

        try {
            $orders->createFromCart($userB);
            $this->fail('Second checkout should fail when stock is already reserved.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('موجودی', $exception->getMessage());
        }

        $this->assertSame(0, (int) $product->fresh()->stock);
        $this->assertSame(1, Order::query()->count());
    }

    public function test_double_mark_paid_does_not_duplicate_enrollment(): void
    {
        $user = User::factory()->create();
        $product = $this->courseProduct(120_000);
        $this->addToCart($user, $product);
        $order = app(OrderService::class)->createFromCart($user);

        $orders = app(OrderService::class);
        $orders->markPaid($order);
        $orders->markPaid($order->fresh());

        $this->assertSame(1, CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $product->course->id)
            ->count());
        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_parallel_stock_reservation_keeps_non_negative_inventory(): void
    {
        $product = ShopProduct::factory()->physical()->create([
            'price' => 60_000,
            'stock' => 1,
            'is_published' => true,
        ]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $this->addToCart($userA, $product);
        $this->addToCart($userB, $product);

        $userAId = $userA->id;
        $userBId = $userB->id;

        $results = Concurrency::run([
            function () use ($userAId) {
                try {
                    $user = User::query()->findOrFail($userAId);
                    auth()->login($user);

                    return ['ok' => true, 'order_id' => app(OrderService::class)->createFromCart($user)->id];
                } catch (\Throwable $e) {
                    return ['ok' => false, 'error' => $e->getMessage()];
                }
            },
            function () use ($userBId) {
                try {
                    $user = User::query()->findOrFail($userBId);
                    auth()->login($user);

                    return ['ok' => true, 'order_id' => app(OrderService::class)->createFromCart($user)->id];
                } catch (\Throwable $e) {
                    return ['ok' => false, 'error' => $e->getMessage()];
                }
            },
        ]);

        $successes = collect($results)->where('ok', true)->count();
        $this->assertSame(1, $successes, 'Exactly one concurrent checkout may reserve the last unit. Results: '.json_encode($results));
        $this->assertGreaterThanOrEqual(0, (int) $product->fresh()->stock);
        $this->assertSame(1, Order::query()->count());
    }

    private function courseProduct(int $price): ShopProduct
    {
        $product = ShopProduct::factory()->course()->create([
            'price' => $price,
            'is_published' => true,
        ]);
        Course::factory()->create([
            'shop_product_id' => $product->id,
            'instructor_id' => null,
        ]);

        return $product->fresh('course');
    }

    private function addToCart(User $user, ShopProduct $product): void
    {
        $this->actingAs($user);
        app(CartService::class)->add($product, 1);
    }

    private function pendingDiscountedOrder(User $user, ShopProduct $product, Coupon $coupon): Order
    {
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => Order::STATUS_PENDING,
            'subtotal' => $product->price,
            'discount' => (int) round($product->price * 0.1),
            'total' => (int) round($product->price * 0.9),
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ]);

        $order->items()->create([
            'shop_product_id' => $product->id,
            'title' => $product->title,
            'price' => $product->price,
            'quantity' => 1,
            'metadata' => ['type' => $product->type, 'stock_reserved' => false],
        ]);

        return $order;
    }
}
