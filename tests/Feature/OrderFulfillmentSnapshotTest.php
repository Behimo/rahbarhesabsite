<?php

namespace Tests\Feature;

use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\CartItem;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderFulfillmentSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_enrolls_the_courses_saved_at_purchase_time(): void
    {
        $user = User::factory()->create();
        [$bundle, $kept, $removed] = $this->bundleWithTwoCourses();
        $order = $this->orderFor($user, $bundle);

        $bundle->bundledCourses()->detach($removed->course->id);
        $bundle->delete();

        app(OrderService::class)->markPaid($order);

        $this->assertTrue($user->fresh()->isEnrolledIn($kept->course));
        $this->assertTrue($user->fresh()->isEnrolledIn($removed->course));
        $this->assertSame($bundle->title, $order->items()->first()->title);
        $this->assertSame(200000, (int) $order->items()->first()->price);
    }

    public function test_a_deleted_snapshot_course_is_skipped(): void
    {
        $user = User::factory()->create();
        [$bundle, $kept, $removed] = $this->bundleWithTwoCourses();
        $order = $this->orderFor($user, $bundle);
        $removed->course->delete();

        app(OrderService::class)->markPaid($order->fresh());

        $this->assertTrue($user->fresh()->isEnrolledIn($kept->course));
        $this->assertSame(1, CourseEnrollment::query()->where('user_id', $user->id)->count());
    }

    public function test_orders_without_a_snapshot_follow_the_live_catalog(): void
    {
        $user = User::factory()->create();
        [$bundle, $kept, $added] = $this->bundleWithTwoCourses();
        $bundle->bundledCourses()->detach($added->course->id);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => Order::STATUS_PENDING,
            'subtotal' => 200000,
            'discount' => 0,
            'total' => 200000,
        ]);
        $order->items()->create([
            'shop_product_id' => $bundle->id,
            'title' => $bundle->title,
            'price' => 200000,
            'quantity' => 1,
            'metadata' => ['type' => ShopProduct::TYPE_BUNDLE],
        ]);
        $bundle->bundledCourses()->attach($added->course->id, ['sort_order' => 2]);

        app(OrderService::class)->markPaid($order);

        $this->assertTrue($user->fresh()->isEnrolledIn($kept->course));
        $this->assertTrue($user->fresh()->isEnrolledIn($added->course));
    }

    public function test_retry_licenses_uses_the_purchased_course_list(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        [$bundle, $kept, $removed] = $this->bundleWithTwoCourses();
        $kept->course->update(['spotplayer_course_id' => 'kept-spot']);
        $removed->course->update(['spotplayer_course_id' => 'removed-spot']);
        $order = $this->orderFor($user, $bundle);
        $order->update(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
        $bundle->bundledCourses()->detach();
        $extra = $this->courseProduct('extra-course');
        $extra->course->update(['spotplayer_course_id' => 'extra-spot']);
        $bundle->bundledCourses()->attach($extra->course->id);

        app(OrderService::class)->retryLicenses($order->fresh('items'));

        Queue::assertPushed(IssueSpotplayerLicenseJob::class, 2);
        Queue::assertPushed(IssueSpotplayerLicenseJob::class, fn (IssueSpotplayerLicenseJob $job) => $job->courseId === $kept->course->id);
        Queue::assertPushed(IssueSpotplayerLicenseJob::class, fn (IssueSpotplayerLicenseJob $job) => $job->courseId === $removed->course->id);
        Queue::assertNotPushed(IssueSpotplayerLicenseJob::class, fn (IssueSpotplayerLicenseJob $job) => $job->courseId === $extra->course->id);
    }

    /** @return array{0: ShopProduct, 1: ShopProduct, 2: ShopProduct} */
    private function bundleWithTwoCourses(): array
    {
        $kept = $this->courseProduct('kept-course');
        $removed = $this->courseProduct('removed-course');
        $bundle = ShopProduct::query()->create([
            'slug' => 'bundle-'.uniqid(),
            'title' => 'بسته حسابداری',
            'price' => 200000,
            'type' => ShopProduct::TYPE_BUNDLE,
            'is_published' => true,
        ]);
        $bundle->bundledCourses()->attach([
            $kept->course->id => ['sort_order' => 1],
            $removed->course->id => ['sort_order' => 2],
        ]);

        return [$bundle, $kept, $removed];
    }

    private function orderFor(User $user, ShopProduct $product): Order
    {
        $item = new CartItem([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'quantity' => 1,
        ]);

        return app(OrderService::class)->createFromCart($user, collect([$item]), null);
    }

    private function courseProduct(string $slug): ShopProduct
    {
        $product = ShopProduct::query()->create([
            'slug' => $slug.'-'.uniqid(),
            'title' => $slug,
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
