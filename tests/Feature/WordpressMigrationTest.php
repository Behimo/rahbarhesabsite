<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\WordpressMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class WordpressMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.wordpress' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('wordpress');
        $this->createWordpressTables();
    }

    protected function tearDown(): void
    {
        DB::disconnect('wordpress');
        DB::purge('wordpress');

        parent::tearDown();
    }

    public function test_order_migration_is_atomic_and_can_resume_without_duplicate_items(): void
    {
        $user = User::factory()->create(['wp_id' => 77]);
        $product = ShopProduct::query()->create([
            'slug' => 'wp-course-atomic',
            'title' => 'دوره واردشده',
            'price' => 250000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);
        $course = $product->course()->create(['level' => 'beginner']);
        $this->addWordpressOrder(501, 9001, 7001, $user->wp_id ?? 77, $product->slug);

        $failItemCreation = true;
        OrderItem::creating(function () use (&$failItemCreation): void {
            if ($failItemCreation) {
                throw new RuntimeException('Injected order item failure.');
            }
        });

        try {
            app(WordpressMigrationService::class)->migrateOrders();
            $this->fail('The injected error should abort the order migration.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected order item failure.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('orders', ['wp_id' => 501]);
        $this->assertDatabaseMissing('order_items', ['title' => 'دوره واردشده']);
        $this->assertDatabaseMissing('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $failItemCreation = false;
        $service = app(WordpressMigrationService::class);

        $this->assertSame(['created' => 1, 'enrolled' => 1], $service->migrateOrders());
        $this->assertSame(['created' => 0, 'enrolled' => 0], $service->migrateOrders());
        $this->assertSame(1, Order::query()->where('wp_id', 501)->count());
        $this->assertSame(1, OrderItem::query()->where('order_id', Order::query()->where('wp_id', 501)->value('id'))->count());
        $this->assertSame(1, CourseEnrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->count());
    }

    public function test_existing_incomplete_order_is_resumed_without_duplicate_rows(): void
    {
        $user = User::factory()->create(['wp_id' => 79]);
        $product = ShopProduct::query()->create([
            'slug' => 'wp-course-resume',
            'title' => 'دورهٔ قابل ادامه',
            'price' => 150000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);
        $course = $product->course()->create(['level' => 'beginner']);
        Order::query()->create([
            'wp_id' => 503,
            'user_id' => $user->id,
            'order_number' => 'WP-503',
            'status' => Order::STATUS_PAID,
            'subtotal' => 150000,
            'total' => 150000,
            'paid_at' => now(),
        ]);
        $this->addWordpressOrder(503, 9003, 7003, 79, $product->slug);

        $service = app(WordpressMigrationService::class);
        $service->migrateOrders();
        $service->migrateOrders();

        $order = Order::query()->where('wp_id', 503)->firstOrFail();
        $this->assertSame(1, $order->items()->count());
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'metadata->wp_order_item_id' => '9003',
        ]);
        $this->assertSame(1, CourseEnrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->count());
    }

    public function test_order_migration_dry_run_does_not_write_local_records(): void
    {
        $user = User::factory()->create(['wp_id' => 78]);
        $this->addWordpressOrder(502, 9002, 7002, $user->wp_id ?? 78, 'not-imported');

        $this->assertSame(
            ['created' => 1, 'enrolled' => 0],
            app(WordpressMigrationService::class)->migrateOrders(dryRun: true)
        );
        $this->assertDatabaseMissing('orders', ['wp_id' => 502]);
    }

    private function createWordpressTables(): void
    {
        Schema::connection('wordpress')->create('posts', function ($table): void {
            $table->unsignedBigInteger('ID')->primary();
            $table->string('post_type')->nullable();
            $table->string('post_status')->nullable();
            $table->string('post_name')->nullable();
            $table->string('post_title')->nullable();
            $table->text('post_content')->nullable();
            $table->text('post_excerpt')->nullable();
            $table->string('post_date')->nullable();
        });

        Schema::connection('wordpress')->create('postmeta', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
        });

        Schema::connection('wordpress')->create('woocommerce_order_items', function ($table): void {
            $table->unsignedBigInteger('order_item_id')->primary();
            $table->unsignedBigInteger('order_id');
            $table->string('order_item_name');
            $table->string('order_item_type');
        });

        Schema::connection('wordpress')->create('woocommerce_order_itemmeta', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('order_item_id');
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
        });
    }

    private function addWordpressOrder(int $orderId, int $itemId, int $wpProductId, int $wpUserId, string $slug): void
    {
        $connection = DB::connection('wordpress');
        $connection->table('posts')->insert([
            [
                'ID' => $orderId,
                'post_type' => 'shop_order',
                'post_status' => 'wc-completed',
                'post_name' => null,
                'post_title' => null,
                'post_content' => null,
                'post_excerpt' => null,
                'post_date' => '2026-10-01 12:00:00',
            ],
            [
                'ID' => $wpProductId,
                'post_type' => 'product',
                'post_status' => 'publish',
                'post_name' => $slug,
                'post_title' => 'Imported product',
                'post_content' => null,
                'post_excerpt' => null,
                'post_date' => null,
            ],
        ]);
        $connection->table('postmeta')->insert([
            ['post_id' => $orderId, 'meta_key' => '_customer_user', 'meta_value' => (string) $wpUserId],
            ['post_id' => $orderId, 'meta_key' => '_order_total', 'meta_value' => '250000'],
            ['post_id' => $orderId, 'meta_key' => '_cart_discount', 'meta_value' => '0'],
        ]);
        $connection->table('woocommerce_order_items')->insert([
            'order_item_id' => $itemId,
            'order_id' => $orderId,
            'order_item_name' => 'دوره واردشده',
            'order_item_type' => 'line_item',
        ]);
        $connection->table('woocommerce_order_itemmeta')->insert([
            ['order_item_id' => $itemId, 'meta_key' => '_product_id', 'meta_value' => (string) $wpProductId],
            ['order_item_id' => $itemId, 'meta_key' => '_line_total', 'meta_value' => '250000'],
            ['order_item_id' => $itemId, 'meta_key' => '_qty', 'meta_value' => '1'],
        ]);
    }
}
