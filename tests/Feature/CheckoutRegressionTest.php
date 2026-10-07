<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for checkout/cart bugs (T2, T3 in docs/BUG-HUNT-REPORT.md).
 *
 * Desired behaviour: a guest cart survives login, and repurchasing an expired
 * course refreshes access.
 */
class CheckoutRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_survives_password_login(): void
    {
        $user = User::factory()->create();
        $product = ShopProduct::factory()->create();

        $this->post(route('cart.add', $product))->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'shop_product_id' => $product->id,
            'user_id' => null,
        ]);

        $this->post(route('login.password'), [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $this->assertDatabaseHas('cart_items', [
            'shop_product_id' => $product->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_repurchase_after_expired_enrollment_restores_access(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()->subMonths(13),
            'expires_at' => now()->subDay(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);

        $this->assertFalse($user->fresh()->isEnrolledIn($course));

        app(OrderService::class)->enrollUser($user, $course, null, CourseEnrollment::SOURCE_PURCHASE);

        $enrollment = CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $this->assertNotNull($enrollment->expires_at, 'Repurchase must extend expires_at.');
        $this->assertTrue(
            $enrollment->expires_at->isFuture(),
            'User paid again but the enrollment stays expired (OrderService::enrollUser does not refresh expires_at).'
        );
        $this->assertTrue($user->fresh()->isEnrolledIn($course));
    }
}
