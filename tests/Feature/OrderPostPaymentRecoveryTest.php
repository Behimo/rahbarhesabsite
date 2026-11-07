<?php

namespace Tests\Feature;

use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\OrderPostPaymentTask;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderPostPaymentRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_paid_finishes_an_open_coupon_task_without_repeating_license_or_enrollment(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $product = ShopProduct::query()->create([
            'slug' => 'recover-course-'.uniqid(),
            'title' => 'دوره بازیابی',
            'price' => 100000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'shop_product_id' => $product->id,
            'level' => 'beginner',
            'spotplayer_course_id' => 'recover-spot',
        ]);
        $coupon = Coupon::query()->create([
            'code' => 'RECOVER10',
            'type' => 'percentage_cart',
            'value' => 10,
            'is_active' => true,
        ]);
        CartItem::query()->create([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'quantity' => 1,
        ]);

        $line = new CartItem([
            'user_id' => $user->id,
            'shop_product_id' => $product->id,
            'quantity' => 1,
        ]);
        $line->setRelation('product', $product->fresh('course'));
        $order = app(OrderService::class)->createFromCart($user, collect([$line]), $coupon);
        app(OrderService::class)->markPaid($order);

        $this->assertSame(1, CourseEnrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->count());
        $this->assertSame(1, CouponUsage::query()->where('order_id', $order->id)->count());
        $this->assertDatabaseMissing('cart_items', ['user_id' => $user->id, 'shop_product_id' => $product->id]);
        Queue::assertPushed(IssueSpotplayerLicenseJob::class, 1);

        CouponUsage::query()->where('order_id', $order->id)->delete();
        $coupon->update(['used_count' => 0]);
        OrderPostPaymentTask::query()
            ->where('order_id', $order->id)
            ->where('type', OrderPostPaymentTask::TYPE_COUPON)
            ->update(['completed_at' => null]);

        Queue::fake();
        app(OrderService::class)->markPaid($order->fresh());

        $this->assertSame(1, CouponUsage::query()->where('order_id', $order->id)->count());
        $this->assertSame(1, CourseEnrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->count());
        Queue::assertNotPushed(IssueSpotplayerLicenseJob::class);
        $this->assertNotNull(
            OrderPostPaymentTask::query()
                ->where('order_id', $order->id)
                ->where('type', OrderPostPaymentTask::TYPE_LICENSE)
                ->value('completed_at')
        );
    }
}
