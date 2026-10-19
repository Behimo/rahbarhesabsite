<?php

namespace Tests\Feature;

use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseSection;
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

    public function test_guest_cannot_refresh_license(): void
    {
        $product = $this->freeCourse('sp-refresh-guest');

        $this->post(route('courses.license.refresh', $product->slug))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('spotplayer_licenses', [
            'course_id' => $product->course->id,
        ]);
    }

    public function test_unenrolled_user_cannot_refresh_license(): void
    {
        $user = User::factory()->create();
        $product = $this->freeCourse('sp-refresh-unenrolled');
        $this->fakeSpotplayer(Http::response(['_id' => 'x', 'license_key' => 'KEY'], 200));

        $this->actingAs($user)
            ->post(route('courses.license.refresh', $product->slug))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_expired_enrollment_cannot_refresh_license(): void
    {
        $user = User::factory()->create();
        $product = $this->freeCourse('sp-refresh-expired');
        CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'enrolled_at' => now()->subMonths(2),
            'expires_at' => now()->subMinute(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);
        $this->fakeSpotplayer(Http::response(['_id' => 'x', 'license_key' => 'KEY'], 200));

        $this->actingAs($user)
            ->post(route('courses.license.refresh', $product->slug))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_refresh_without_spotplayer_course_id_returns_error(): void
    {
        $user = User::factory()->create();
        $product = $this->freeCourse(null);
        $this->enroll($user, $product->course);
        $this->fakeSpotplayer(Http::response(['_id' => 'x', 'license_key' => 'KEY'], 200));

        $this->actingAs($user)
            ->from(route('courses.show', $product->slug))
            ->post(route('courses.license.refresh', $product->slug))
            ->assertRedirect(route('courses.show', $product->slug))
            ->assertSessionHas('error', 'این دوره به اسپات‌پلیر متصل نیست. با پشتیبانی تماس بگیرید.');

        Http::assertNothingSent();
    }

    public function test_refresh_issues_license_on_successful_api(): void
    {
        $user = User::factory()->create();
        $product = $this->freeCourse('sp-refresh-ok');
        $this->enroll($user, $product->course);
        $this->fakeSpotplayer(Http::response([
            '_id' => 'spot-r1',
            'license_key' => 'KEY-R1',
            'url' => 'https://app.spotplayer.ir/?license=KEY-R1',
        ], 200));

        $this->actingAs($user)
            ->from(route('courses.show', $product->slug))
            ->post(route('courses.license.refresh', $product->slug))
            ->assertRedirect(route('courses.show', $product->slug))
            ->assertSessionHas('success', 'دسترسی اسپات‌پلیر آماده شد.');

        $this->assertDatabaseHas('spotplayer_licenses', [
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'license_key' => 'KEY-R1',
            'status' => SpotplayerLicense::STATUS_ISSUED,
        ]);
        Http::assertSentCount(1);
    }

    public function test_refresh_failed_api_keeps_enrollment_and_flashes_error(): void
    {
        $user = User::factory()->create();
        $product = $this->freeCourse('sp-refresh-fail');
        $this->enroll($user, $product->course);
        $this->fakeSpotplayer(Http::response(['message' => 'rejected'], 422));

        $this->actingAs($user)
            ->from(route('courses.show', $product->slug))
            ->post(route('courses.license.refresh', $product->slug))
            ->assertRedirect(route('courses.show', $product->slug))
            ->assertSessionHas('error', 'صدور لایسنس ناموفق بود. لطفاً با پشتیبانی تماس بگیرید.');

        $this->assertTrue($user->fresh()->isEnrolledIn($product->course));
        $this->assertDatabaseHas('spotplayer_licenses', [
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'status' => SpotplayerLicense::STATUS_FAILED,
        ]);
    }

    public function test_refresh_when_already_issued_does_not_call_api_again(): void
    {
        $user = User::factory()->create();
        $product = $this->freeCourse('sp-refresh-idempotent');
        $this->enroll($user, $product->course);
        SpotplayerLicense::query()->create([
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'license_key' => 'EXISTING-KEY',
            'status' => SpotplayerLicense::STATUS_ISSUED,
        ]);
        $this->fakeSpotplayer(Http::response([
            '_id' => 'spot-new',
            'license_key' => 'NEW-KEY',
        ], 200));

        $this->actingAs($user)
            ->from(route('courses.show', $product->slug))
            ->post(route('courses.license.refresh', $product->slug))
            ->assertSessionHas('success', 'دسترسی اسپات‌پلیر آماده شد.');

        Http::assertNothingSent();
        $this->assertSame(1, SpotplayerLicense::query()->count());
        $this->assertDatabaseHas('spotplayer_licenses', [
            'user_id' => $user->id,
            'course_id' => $product->course->id,
            'license_key' => 'EXISTING-KEY',
        ]);
    }

    public function test_free_preview_lesson_is_publicly_viewable(): void
    {
        [$product, $lesson] = $this->courseWithPreviewLesson(true);

        $this->get(route('courses.preview', [$product->slug, $lesson->slug]))
            ->assertOk()
            ->assertViewIs('pages.courses.preview')
            ->assertSee($lesson->title);
    }

    public function test_non_preview_lesson_returns_forbidden(): void
    {
        [$product, $lesson] = $this->courseWithPreviewLesson(false);

        $this->get(route('courses.preview', [$product->slug, $lesson->slug]))
            ->assertForbidden();
    }

    public function test_unknown_preview_lesson_slug_returns_forbidden(): void
    {
        [$product] = $this->courseWithPreviewLesson(true);

        $this->get(route('courses.preview', [$product->slug, 'missing-lesson']))
            ->assertForbidden();
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

    private function enroll(User $user, Course $course): CourseEnrollment
    {
        return CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'expires_at' => now()->addYear(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);
    }

    /** @return array{0: ShopProduct, 1: CourseLesson} */
    private function courseWithPreviewLesson(bool $isFreePreview): array
    {
        $product = ShopProduct::factory()->course()->create();
        $course = Course::factory()->create([
            'shop_product_id' => $product->id,
            'instructor_id' => null,
        ]);
        $section = CourseSection::factory()->create([
            'course_id' => $course->id,
            'sort_order' => 1,
        ]);
        $lesson = CourseLesson::factory()->create([
            'section_id' => $section->id,
            'slug' => 'intro-preview',
            'title' => 'درس پیش‌نمایش',
            'is_free_preview' => $isFreePreview,
            'sort_order' => 1,
        ]);

        return [$product->fresh(), $lesson];
    }
}
