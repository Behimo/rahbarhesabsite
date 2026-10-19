<?php

namespace Tests\Feature;

use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\SpotplayerLicense;
use App\Models\User;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminLicenseOrderActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_licenses_index(): void
    {
        $this->get(route('admin.licenses.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_editor_cannot_view_licenses_index(): void
    {
        $editor = $this->makeEditor();

        $this->actingAs($editor)
            ->get(route('admin.licenses.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_licenses_index_and_show(): void
    {
        $admin = $this->makeAdmin();
        $license = $this->makeLicense();

        $this->actingAs($admin)
            ->get(route('admin.licenses.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.licenses.show', $license))
            ->assertOk();
    }

    public function test_admin_can_reissue_license(): void
    {
        $admin = $this->makeAdmin();
        $license = $this->makeLicense(['status' => SpotplayerLicense::STATUS_FAILED, 'license_key' => null]);
        $this->fakeSpotplayer(Http::response([
            '_id' => 'spot-reissue',
            'license_key' => 'KEY-REISSUE',
            'url' => 'https://app.spotplayer.ir/?license=KEY-REISSUE',
        ], 200));

        $this->actingAs($admin)
            ->post(route('admin.licenses.reissue', $license))
            ->assertRedirect();

        $this->assertDatabaseHas('spotplayer_licenses', [
            'user_id' => $license->user_id,
            'course_id' => $license->course_id,
            'license_key' => 'KEY-REISSUE',
            'status' => SpotplayerLicense::STATUS_ISSUED,
        ]);
    }

    public function test_editor_cannot_reissue_license(): void
    {
        $editor = $this->makeEditor();
        $license = $this->makeLicense();

        $this->actingAs($editor)
            ->post(route('admin.licenses.reissue', $license))
            ->assertForbidden();
    }

    public function test_admin_retry_licenses_queues_jobs(): void
    {
        Queue::fake();
        $admin = $this->makeAdmin();
        [$order, $user, $product] = $this->paidCourseOrder();
        $product->course->update(['spotplayer_course_id' => 'sp-retry-1']);
        CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'order_id' => $order->id,
            'enrolled_at' => now(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.retry-licenses', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');

        Queue::assertPushed(IssueSpotplayerLicenseJob::class, function (IssueSpotplayerLicenseJob $job) use ($user, $product, $order) {
            return $job->userId === $user->id
                && $job->courseId === $product->course->id
                && $job->orderId === $order->id;
        });
    }

    public function test_editor_cannot_retry_licenses(): void
    {
        $editor = $this->makeEditor();
        [$order] = $this->paidCourseOrder();

        $this->actingAs($editor)
            ->post(route('admin.orders.retry-licenses', $order))
            ->assertForbidden();
    }

    public function test_admin_can_assign_user_to_orphan_paid_order(): void
    {
        $admin = $this->makeAdmin();
        $assignee = User::factory()->create();
        $product = $this->courseProduct();
        $order = Order::query()->create([
            'user_id' => null,
            'order_number' => Order::generateOrderNumber(),
            'status' => Order::STATUS_PAID,
            'subtotal' => 100_000,
            'discount' => 0,
            'total' => 100_000,
            'needs_review' => true,
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'shop_product_id' => $product->id,
            'title' => $product->title,
            'price' => 100_000,
            'quantity' => 1,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.assign-user', $order), ['user_id' => $assignee->id])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame($assignee->id, $order->user_id);
        $this->assertFalse($order->needs_review);
        $this->assertTrue($assignee->fresh()->isEnrolledIn($product->course));
    }

    public function test_assign_user_requires_valid_user_id(): void
    {
        $admin = $this->makeAdmin();
        [$order] = $this->paidCourseOrder();
        $order->update(['needs_review' => true]);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.assign-user', $order), [])
            ->assertSessionHasErrors('user_id');
    }

    public function test_admin_can_enroll_user_manually(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $product = $this->courseProduct();

        $this->actingAs($admin)
            ->from(route('admin.orders.index'))
            ->post(route('admin.orders.enroll'), [
                'user_id' => $user->id,
                'course_id' => $product->course->id,
                'source' => CourseEnrollment::SOURCE_MANUAL,
            ])
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'source' => CourseEnrollment::SOURCE_MANUAL,
            'status' => CourseEnrollment::STATUS_ACTIVE,
        ]);
    }

    public function test_enroll_rejects_invalid_source(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $product = $this->courseProduct();

        $this->actingAs($admin)
            ->from(route('admin.orders.index'))
            ->post(route('admin.orders.enroll'), [
                'user_id' => $user->id,
                'course_id' => $product->course->id,
                'source' => 'purchase',
            ])
            ->assertSessionHasErrors('source');
    }

    public function test_editor_cannot_enroll_user(): void
    {
        $editor = $this->makeEditor();
        $user = User::factory()->create();
        $product = $this->courseProduct();

        $this->actingAs($editor)
            ->post(route('admin.orders.enroll'), [
                'user_id' => $user->id,
                'course_id' => $product->course->id,
                'source' => CourseEnrollment::SOURCE_MANUAL,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_revoke_enrollment(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $product = $this->courseProduct();
        $enrollment = CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'enrolled_at' => now(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_MANUAL,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.orders.index'))
            ->post(route('admin.orders.enrollments.revoke', $enrollment))
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHas('success');

        $this->assertSame(CourseEnrollment::STATUS_REVOKED, $enrollment->fresh()->status);
        $this->assertFalse($user->fresh()->isEnrolledIn($product->course));
    }

    public function test_editor_cannot_revoke_enrollment(): void
    {
        $editor = $this->makeEditor();
        $user = User::factory()->create();
        $product = $this->courseProduct();
        $enrollment = CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'enrolled_at' => now(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_MANUAL,
        ]);

        $this->actingAs($editor)
            ->post(route('admin.orders.enrollments.revoke', $enrollment))
            ->assertForbidden();
    }

    private function makeEditor(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole(AccessCatalog::ROLE_EDITOR);

        return $editor;
    }

    private function courseProduct(?string $spotplayerCourseId = 'sp-admin-1'): ShopProduct
    {
        $product = ShopProduct::factory()->course()->create([
            'price' => 100_000,
        ]);
        Course::factory()->create([
            'shop_product_id' => $product->id,
            'instructor_id' => null,
            'spotplayer_course_id' => $spotplayerCourseId,
        ]);

        return $product->fresh('course');
    }

    /** @return array{0: Order, 1: User, 2: ShopProduct} */
    private function paidCourseOrder(): array
    {
        $user = User::factory()->create();
        $product = $this->courseProduct();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => Order::STATUS_PAID,
            'subtotal' => 100_000,
            'discount' => 0,
            'total' => 100_000,
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'shop_product_id' => $product->id,
            'title' => $product->title,
            'price' => 100_000,
            'quantity' => 1,
        ]);

        return [$order, $user, $product];
    }

    private function makeLicense(array $overrides = []): SpotplayerLicense
    {
        $user = User::factory()->create();
        $product = $this->courseProduct();

        return SpotplayerLicense::query()->create(array_merge([
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'license_key' => 'KEY-EXISTING',
            'status' => SpotplayerLicense::STATUS_ISSUED,
        ], $overrides));
    }

    private function fakeSpotplayer(mixed $response): void
    {
        config([
            'cms.spotplayer.api_key' => 'test-key',
            'cms.spotplayer.base_url' => 'https://spotplayer.test',
        ]);

        Http::fake([
            'https://spotplayer.test/licenses' => $response,
        ]);
    }
}
