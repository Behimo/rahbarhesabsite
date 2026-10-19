<?php

namespace Tests\Feature;

use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\Category;
use App\Models\CmsPost;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopProduct;
use App\Models\Tag;
use App\Models\User;
use App\Services\WordpressMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
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

        $this->assertSame(
            ['created' => 1, 'enrolled' => 1, 'needs_review' => 0, 'linked_by_phone' => 0],
            $service->migrateOrders()
        );
        $this->assertSame(
            ['created' => 0, 'enrolled' => 0, 'needs_review' => 0, 'linked_by_phone' => 0],
            $service->migrateOrders()
        );
        $this->assertSame(1, Order::query()->where('wp_id', 501)->count());
        $this->assertSame(1, OrderItem::query()->where('order_id', Order::query()->where('wp_id', 501)->value('id'))->count());
        $this->assertSame(1, CourseEnrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->count());
    }

    public function test_historical_order_creates_enrollment_without_issuing_a_new_spotplayer_license(): void
    {
        Queue::fake();
        $user = User::factory()->create(['wp_id' => 80]);
        $product = ShopProduct::query()->create([
            'slug' => 'historical-spot-course',
            'title' => 'دوره تاریخی',
            'price' => 250000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);
        $course = $product->course()->create([
            'level' => 'beginner',
            'spotplayer_course_id' => 'remote-historical-course',
        ]);
        $this->addWordpressOrder(504, 9004, 7004, 80, $product->slug);

        app(WordpressMigrationService::class)->migrateOrders();
        app(WordpressMigrationService::class)->migrateOrders();

        $this->assertSame(1, CourseEnrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->count());
        Queue::assertNotPushed(IssueSpotplayerLicenseJob::class);
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
            ['created' => 1, 'enrolled' => 0, 'needs_review' => 0, 'linked_by_phone' => 0],
            app(WordpressMigrationService::class)->migrateOrders(dryRun: true)
        );
        $this->assertDatabaseMissing('orders', ['wp_id' => 502]);
    }

    public function test_users_without_mobile_are_imported_as_pending_activation(): void
    {
        $this->seedWordpressUser(11, 'no-phone-user', 'nophone@example.com', 'hash11', []);
        $this->seedWordpressUser(12, '09121234567', 'withphone@example.com', 'hash12', [
            'billing_phone' => '09121234567',
        ]);

        $stats = app(WordpressMigrationService::class)->migrateUsers();

        $this->assertSame(2, $stats['created']);
        $this->assertSame(1, $stats['pending_activation']);
        $this->assertDatabaseHas('users', [
            'wp_id' => 11,
            'status' => User::STATUS_PENDING_ACTIVATION,
            'mobile' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'wp_id' => 12,
            'status' => User::STATUS_ACTIVE,
            'mobile' => '09121234567',
        ]);
    }

    public function test_duplicate_mobile_keeps_separate_users_and_orders_follow_wp_id(): void
    {
        $this->seedWordpressUser(21, '09121111111', 'first@example.com', 'hash21', [
            'billing_phone' => '09120000000',
        ]);
        $this->seedWordpressUser(22, 'second', 'second@example.com', 'hash22', [
            'billing_phone' => '09120000000',
        ]);

        $product = ShopProduct::query()->create([
            'slug' => 'dup-course',
            'title' => 'دوره تکراری',
            'price' => 100000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);
        $course = $product->course()->create(['level' => 'beginner']);

        // User 22 has more paid orders → wins the mobile.
        $this->addWordpressOrder(601, 9601, 7601, 22, $product->slug);
        $this->addWordpressOrder(602, 9602, 7601, 22, $product->slug);
        $this->addWordpressOrder(603, 9603, 7601, 21, $product->slug);

        $userStats = app(WordpressMigrationService::class)->migrateUsers();
        app(WordpressMigrationService::class)->migrateOrders();

        $winner = User::query()->where('wp_id', 22)->firstOrFail();
        $loser = User::query()->where('wp_id', 21)->firstOrFail();

        $this->assertSame('09120000000', $winner->mobile);
        $this->assertSame(User::STATUS_ACTIVE, $winner->status);
        $this->assertNull($loser->mobile);
        $this->assertSame('09120000000', $loser->claimed_mobile);
        $this->assertSame(User::STATUS_PENDING_ACTIVATION, $loser->status);
        $this->assertSame(1, $userStats['duplicate_mobile']);
        $this->assertSame($loser->id, Order::query()->where('wp_id', 603)->value('user_id'));
        $this->assertSame($winner->id, Order::query()->where('wp_id', 601)->value('user_id'));
        $this->assertTrue(CourseEnrollment::query()->where('user_id', $loser->id)->where('course_id', $course->id)->exists());
    }

    public function test_guest_order_without_phone_is_imported_for_admin_review(): void
    {
        $this->addWordpressOrder(701, 9701, 7701, 0, 'orphan-course', billingPhone: 'bad');

        $stats = app(WordpressMigrationService::class)->migrateOrders();

        $this->assertSame(1, $stats['created']);
        $this->assertSame(1, $stats['needs_review']);
        $this->assertDatabaseHas('orders', [
            'wp_id' => 701,
            'user_id' => null,
            'needs_review' => 1,
        ]);
    }

    public function test_guest_order_with_billing_phone_links_or_creates_user(): void
    {
        ShopProduct::query()->create([
            'slug' => 'guest-course',
            'title' => 'دوره مهمان',
            'price' => 120000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ])->course()->create(['level' => 'beginner']);

        $this->addWordpressOrder(702, 9702, 7702, 0, 'guest-course', billingPhone: '09351234567');

        $stats = app(WordpressMigrationService::class)->migrateOrders();

        $this->assertSame(1, $stats['linked_by_phone']);
        $this->assertSame(0, $stats['needs_review']);
        $order = Order::query()->where('wp_id', 702)->firstOrFail();
        $this->assertNotNull($order->user_id);
        $this->assertFalse($order->needs_review);
        $this->assertDatabaseHas('users', ['mobile' => '09351234567']);
    }

    public function test_term_links_connect_products_and_posts_to_categories_and_tags(): void
    {
        $productCat = Category::factory()->product()->create([
            'slug' => 'tax-courses',
            'name' => 'دوره‌های مالیاتی',
        ]);
        $postCat = Category::factory()->post()->create([
            'slug' => 'blogs',
            'name' => 'مقالات',
        ]);
        Tag::query()->create(['name' => 'سامانه مودیان', 'slug' => 'moodian']);

        $product = ShopProduct::query()->create([
            'slug' => 'moodian-course',
            'title' => 'دوره مودیان',
            'price' => 100000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);
        $post = CmsPost::query()->create([
            'slug' => 'sample-article',
            'title' => 'مقاله نمونه',
            'body' => 'متن',
            'status' => CmsPost::STATUS_PUBLISHED,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->seedWordpressTerm(10, 'دوره‌های مالیاتی', 'tax-courses', 'product_cat');
        $this->seedWordpressTerm(20, 'مقالات', 'blogs', 'category');
        $this->seedWordpressTerm(30, 'سامانه مودیان', 'moodian', 'post_tag');

        DB::connection('wordpress')->table('posts')->insert([
            [
                'ID' => 801,
                'post_type' => 'product',
                'post_status' => 'publish',
                'post_name' => 'moodian-course',
                'post_title' => 'دوره مودیان',
                'post_content' => null,
                'post_excerpt' => null,
                'post_date' => null,
            ],
            [
                'ID' => 802,
                'post_type' => 'post',
                'post_status' => 'publish',
                'post_name' => 'sample-article',
                'post_title' => 'مقاله نمونه',
                'post_content' => null,
                'post_excerpt' => null,
                'post_date' => null,
            ],
        ]);

        $this->attachWordpressTerm(801, 10);
        $this->attachWordpressTerm(802, 20);
        $this->attachWordpressTerm(802, 30);

        $stats = app(WordpressMigrationService::class)->migrateTermLinks();

        $this->assertSame(1, $stats['products_linked']);
        $this->assertSame(1, $stats['posts_linked']);
        $this->assertSame(1, $stats['product_category_links']);
        $this->assertSame(1, $stats['post_category_links']);
        $this->assertSame(1, $stats['post_tag_links']);
        $this->assertTrue($product->fresh()->categories->contains('id', $productCat->id));
        $this->assertTrue($post->fresh()->categories->contains('id', $postCat->id));
        $this->assertTrue($post->fresh()->tags->contains('slug', 'moodian'));
    }

    private function createWordpressTables(): void
    {
        Schema::connection('wordpress')->create('users', function ($table): void {
            $table->unsignedBigInteger('ID')->primary();
            $table->string('user_login');
            $table->string('user_pass')->nullable();
            $table->string('user_email')->nullable();
            $table->string('display_name')->nullable();
        });

        Schema::connection('wordpress')->create('usermeta', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
        });

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

        Schema::connection('wordpress')->create('terms', function ($table): void {
            $table->unsignedBigInteger('term_id')->primary();
            $table->string('name');
            $table->string('slug');
        });

        Schema::connection('wordpress')->create('term_taxonomy', function ($table): void {
            $table->unsignedBigInteger('term_taxonomy_id')->primary();
            $table->unsignedBigInteger('term_id');
            $table->string('taxonomy');
            $table->unsignedBigInteger('parent')->default(0);
            $table->text('description')->nullable();
        });

        Schema::connection('wordpress')->create('term_relationships', function ($table): void {
            $table->unsignedBigInteger('object_id');
            $table->unsignedBigInteger('term_taxonomy_id');
            $table->integer('term_order')->default(0);
        });
    }

    private function seedWordpressTerm(int $termId, string $name, string $slug, string $taxonomy): void
    {
        DB::connection('wordpress')->table('terms')->insert([
            'term_id' => $termId,
            'name' => $name,
            'slug' => $slug,
        ]);
        DB::connection('wordpress')->table('term_taxonomy')->insert([
            'term_taxonomy_id' => $termId,
            'term_id' => $termId,
            'taxonomy' => $taxonomy,
            'parent' => 0,
            'description' => null,
        ]);
    }

    private function attachWordpressTerm(int $objectId, int $termTaxonomyId): void
    {
        DB::connection('wordpress')->table('term_relationships')->insert([
            'object_id' => $objectId,
            'term_taxonomy_id' => $termTaxonomyId,
            'term_order' => 0,
        ]);
    }

    /** @param  array<string, string>  $meta */
    private function seedWordpressUser(int $id, string $login, string $email, string $pass, array $meta): void
    {
        DB::connection('wordpress')->table('users')->insert([
            'ID' => $id,
            'user_login' => $login,
            'user_pass' => $pass,
            'user_email' => $email,
            'display_name' => $login,
        ]);

        foreach ($meta as $key => $value) {
            DB::connection('wordpress')->table('usermeta')->insert([
                'user_id' => $id,
                'meta_key' => $key,
                'meta_value' => $value,
            ]);
        }
    }

    private function addWordpressOrder(
        int $orderId,
        int $itemId,
        int $wpProductId,
        int $wpUserId,
        string $slug,
        ?string $billingPhone = null,
    ): void {
        $connection = DB::connection('wordpress');
        if (! $connection->table('posts')->where('ID', $wpProductId)->exists()) {
            $connection->table('posts')->insert([
                'ID' => $wpProductId,
                'post_type' => 'product',
                'post_status' => 'publish',
                'post_name' => $slug,
                'post_title' => 'Imported product',
                'post_content' => null,
                'post_excerpt' => null,
                'post_date' => null,
            ]);
        }
        $connection->table('posts')->insert([
            'ID' => $orderId,
            'post_type' => 'shop_order',
            'post_status' => 'wc-completed',
            'post_name' => null,
            'post_title' => null,
            'post_content' => null,
            'post_excerpt' => null,
            'post_date' => '2026-10-01 12:00:00',
        ]);
        $meta = [
            ['post_id' => $orderId, 'meta_key' => '_customer_user', 'meta_value' => (string) $wpUserId],
            ['post_id' => $orderId, 'meta_key' => '_order_total', 'meta_value' => '250000'],
            ['post_id' => $orderId, 'meta_key' => '_cart_discount', 'meta_value' => '0'],
        ];
        if ($billingPhone !== null) {
            $meta[] = ['post_id' => $orderId, 'meta_key' => '_billing_phone', 'meta_value' => $billingPhone];
        }
        $connection->table('postmeta')->insert($meta);
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
