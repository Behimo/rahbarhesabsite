<?php

namespace Tests\Feature;

use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\ShopProduct;
use App\Models\SpotplayerLicense;
use App\Models\User;
use App\Services\OrderService;
use App\Services\SpotPlayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SpotplayerLicenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_enrollment_queues_a_spotplayer_license(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $product = $this->freeCourse('sp-course-1');

        $this->actingAs($user)
            ->get(route('courses.enroll-free', $product->slug))
            ->assertRedirect(route('courses.learn', $product->slug))
            ->assertSessionHas('success', 'ثبت‌نام انجام شد. دسترسی اسپات‌پلیر به‌زودی آماده می‌شود.');

        $this->assertTrue($user->fresh()->isEnrolledIn($product->course));
        Queue::assertPushed(IssueSpotplayerLicenseJob::class, function (IssueSpotplayerLicenseJob $job) use ($user, $product) {
            return $job->userId === $user->id
                && $job->courseId === $product->course->id
                && $job->orderId === null;
        });
    }

    public function test_license_job_is_marked_after_commit_when_enrollment_transaction_rolls_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $product = $this->freeCourse('sp-course-rollback');

        try {
            DB::transaction(function () use ($user, $product): void {
                app(OrderService::class)->enrollUser($user, $product->course, null, CourseEnrollment::SOURCE_FREE);
                throw new \RuntimeException('rollback test');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('rollback test', $exception->getMessage());
        }

        $this->assertDatabaseMissing('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $product->course->id,
        ]);
        // Queue::fake records dispatches immediately; it does not simulate the
        // real queue connection's after-commit callbacks on rollback.
        Queue::assertPushed(IssueSpotplayerLicenseJob::class, fn (IssueSpotplayerLicenseJob $job) => $job->afterCommit === true);
    }

    public function test_free_enrollment_without_spotplayer_does_not_queue_a_license(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $product = $this->freeCourse(null);

        $this->actingAs($user)
            ->get(route('courses.enroll-free', $product->slug))
            ->assertRedirect(route('courses.learn', $product->slug));

        Queue::assertNotPushed(IssueSpotplayerLicenseJob::class);
    }

    public function test_failed_spotplayer_api_keeps_the_enrollment(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $product = $this->freeCourse('sp-course-2');

        $this->actingAs($user)->get(route('courses.enroll-free', $product->slug));

        $job = $this->pushedJob();
        $this->fakeSpotplayer(Http::response(['message' => 'rejected'], 422));

        try {
            $job->handle(app(SpotPlayerService::class));
            $this->fail('A failed SpotPlayer response should be retried.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('صدور لایسنس اسپات‌پلیر ناموفق بود', $exception->getMessage());
        }

        $this->assertSame(1, CourseEnrollment::query()->count());
        $this->assertTrue($user->fresh()->isEnrolledIn($product->course));
        $this->assertSame(1, SpotplayerLicense::query()->count());
        $this->assertDatabaseHas('spotplayer_licenses', [
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'status' => SpotplayerLicense::STATUS_FAILED,
        ]);
    }

    public function test_rerunning_the_license_job_does_not_issue_a_second_license(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $product = $this->freeCourse('sp-course-3');

        $this->actingAs($user)->get(route('courses.enroll-free', $product->slug));

        $job = $this->pushedJob();
        $this->fakeSpotplayer(Http::response([
            '_id' => 'spot-1',
            'license_key' => 'KEY-1',
            'url' => 'https://app.spotplayer.ir/?license=KEY-1',
        ], 200));

        $service = app(SpotPlayerService::class);
        $job->handle($service);
        $job->handle($service);

        $this->assertSame(1, SpotplayerLicense::query()->count());
        $this->assertDatabaseHas('spotplayer_licenses', [
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'license_key' => 'KEY-1',
            'status' => SpotplayerLicense::STATUS_ISSUED,
        ]);
        Http::assertSentCount(1);
    }

    public function test_delayed_license_job_does_not_issue_for_an_expired_enrollment(): void
    {
        $user = User::factory()->create();
        $product = $this->freeCourse('sp-course-expired');
        CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'enrolled_at' => now()->subMonths(2),
            'expires_at' => now()->subMinute(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);
        $this->fakeSpotplayer(Http::response([
            '_id' => 'unexpected-license', 'license_key' => 'UNEXPECTED-KEY',
        ]));

        (new IssueSpotplayerLicenseJob($user->id, $product->course->id))
            ->handle(app(SpotPlayerService::class));

        Http::assertNothingSent();
        $this->assertDatabaseMissing('spotplayer_licenses', [
            'user_id' => $user->id,
            'course_id' => $product->course->id,
        ]);
    }

    private function freeCourse(?string $spotplayerCourseId): ShopProduct
    {
        $product = ShopProduct::factory()->course()->free()->create();
        Course::factory()->create([
            'shop_product_id' => $product->id,
            'instructor_id' => null,
            'spotplayer_course_id' => $spotplayerCourseId,
        ]);

        return $product->fresh('course');
    }

    private function pushedJob(): IssueSpotplayerLicenseJob
    {
        $job = null;

        Queue::assertPushed(IssueSpotplayerLicenseJob::class, function (IssueSpotplayerLicenseJob $pushed) use (&$job) {
            $job = $pushed;

            return true;
        });

        $this->assertInstanceOf(IssueSpotplayerLicenseJob::class, $job);

        return $job;
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
