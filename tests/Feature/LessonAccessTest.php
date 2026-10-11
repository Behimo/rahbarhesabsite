<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\LessonProgress;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LessonAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsigned_lesson_file_url_is_rejected(): void
    {
        [$product, , $lessons] = $this->courseWithLessons([
            ['download_url' => 'https://files.example/alpha.pdf'],
        ]);

        $this->get(route('courses.lesson.file', [
            'slug' => $product->slug,
            'lessonSlug' => $lessons[0]->slug,
        ]))->assertForbidden();
    }

    public function test_expired_lesson_file_signature_is_rejected(): void
    {
        [$product, , $lessons] = $this->courseWithLessons([
            ['download_url' => 'https://files.example/alpha.pdf'],
        ]);

        $url = URL::temporarySignedRoute(
            'courses.lesson.file',
            now()->subMinute(),
            ['slug' => $product->slug, 'lessonSlug' => $lessons[0]->slug]
        );

        $this->get($url)->assertForbidden();
    }

    public function test_signature_for_another_lesson_is_rejected(): void
    {
        [$product, , $lessons] = $this->courseWithLessons([
            ['slug' => 'lesson-alpha', 'download_url' => 'https://files.example/alpha.pdf'],
            ['slug' => 'lesson-beta', 'download_url' => 'https://files.example/beta.pdf'],
        ]);

        $signed = URL::temporarySignedRoute(
            'courses.lesson.file',
            now()->addMinutes(30),
            ['slug' => $product->slug, 'lessonSlug' => $lessons[0]->slug]
        );

        $tampered = str_replace('/lessons/lesson-alpha/file', '/lessons/lesson-beta/file', $signed);

        $this->get($tampered)->assertForbidden();
    }

    public function test_valid_signature_redirects_only_to_that_lesson_file(): void
    {
        [$product, , $lessons] = $this->courseWithLessons([
            ['slug' => 'lesson-alpha', 'download_url' => 'https://files.example/alpha.pdf'],
            ['slug' => 'lesson-beta', 'download_url' => 'https://files.example/beta.pdf'],
        ]);

        $alpha = URL::temporarySignedRoute(
            'courses.lesson.file',
            now()->addMinutes(30),
            ['slug' => $product->slug, 'lessonSlug' => $lessons[0]->slug]
        );
        $beta = URL::temporarySignedRoute(
            'courses.lesson.file',
            now()->addMinutes(30),
            ['slug' => $product->slug, 'lessonSlug' => $lessons[1]->slug]
        );

        $this->get($alpha)->assertRedirect('https://files.example/alpha.pdf');
        $this->get($beta)->assertRedirect('https://files.example/beta.pdf');
    }

    public function test_signed_url_for_a_lesson_without_a_file_is_not_found(): void
    {
        [$product, , $lessons] = $this->courseWithLessons([
            ['download_url' => null],
        ]);

        $url = URL::temporarySignedRoute(
            'courses.lesson.file',
            now()->addMinutes(30),
            ['slug' => $product->slug, 'lessonSlug' => $lessons[0]->slug]
        );

        $this->get($url)->assertNotFound();
    }

    public function test_download_requires_an_active_enrollment(): void
    {
        [$product, $course, $lessons] = $this->courseWithLessons([
            ['download_url' => 'https://files.example/alpha.pdf'],
        ]);
        $lesson = $lessons[0];
        $guest = User::factory()->create();
        $enrolled = User::factory()->create();
        $this->enroll($enrolled, $course);

        $this->get(route('courses.lesson.download', [
            'slug' => $product->slug,
            'lessonSlug' => $lesson->slug,
        ]))->assertRedirect(route('login'));

        $this->actingAs($guest)
            ->get(route('courses.lesson.download', [
                'slug' => $product->slug,
                'lessonSlug' => $lesson->slug,
            ]))
            ->assertForbidden();

        $download = $this->actingAs($enrolled)
            ->get(route('courses.lesson.download', [
                'slug' => $product->slug,
                'lessonSlug' => $lesson->slug,
            ]));

        $download->assertRedirect();
        $this->get($download->headers->get('Location'))
            ->assertRedirect('https://files.example/alpha.pdf');
    }

    public function test_guest_cannot_complete_a_lesson(): void
    {
        [$product, , $lessons] = $this->courseWithLessons();

        $this->post(route('courses.lesson.complete', [
            'slug' => $product->slug,
            'lessonSlug' => $lessons[0]->slug,
        ]))->assertRedirect(route('login'));

        $this->assertSame(0, LessonProgress::query()->count());
    }

    public function test_unenrolled_user_cannot_complete_a_lesson(): void
    {
        [$product, , $lessons] = $this->courseWithLessons();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('courses.lesson.complete', [
                'slug' => $product->slug,
                'lessonSlug' => $lessons[0]->slug,
            ]))
            ->assertForbidden();

        $this->assertSame(0, LessonProgress::query()->count());
    }

    public function test_expired_enrollment_cannot_complete_a_lesson(): void
    {
        [$product, $course, $lessons] = $this->courseWithLessons();
        $user = User::factory()->create();

        CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);

        $this->actingAs($user)
            ->post(route('courses.lesson.complete', [
                'slug' => $product->slug,
                'lessonSlug' => $lessons[0]->slug,
            ]))
            ->assertForbidden();

        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lessons[0]->id,
        ]);
    }

    public function test_enrolled_user_completion_updates_course_progress(): void
    {
        [$product, $course, $lessons] = $this->courseWithLessons([
            ['slug' => 'lesson-one'],
            ['slug' => 'lesson-two'],
        ]);
        $user = User::factory()->create();
        $this->enroll($user, $course);

        $this->actingAs($user)
            ->from(route('courses.learn', ['slug' => $product->slug, 'lessonSlug' => 'lesson-one']))
            ->post(route('courses.lesson.complete', [
                'slug' => $product->slug,
                'lessonSlug' => 'lesson-one',
            ]))
            ->assertRedirect(route('courses.learn', ['slug' => $product->slug, 'lessonSlug' => 'lesson-one']))
            ->assertSessionHas('success', 'درس تکمیل شد.');

        $enrollment = $this->enrollment($user, $course);
        $this->assertSame(50, $enrollment->progress_percent);
        $this->assertNull($enrollment->completed_at);

        $this->actingAs($user)
            ->from(route('courses.learn', ['slug' => $product->slug, 'lessonSlug' => 'lesson-two']))
            ->post(route('courses.lesson.complete', [
                'slug' => $product->slug,
                'lessonSlug' => 'lesson-two',
            ]))
            ->assertSessionHas('success', 'درس تکمیل شد.');

        $enrollment->refresh();
        $this->assertSame(100, $enrollment->progress_percent);
        $this->assertNotNull($enrollment->completed_at);
    }

    public function test_repurchase_restores_the_ability_to_complete_lessons(): void
    {
        [$product, $course, $lessons] = $this->courseWithLessons();
        $user = User::factory()->create();

        CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);

        $this->actingAs($user)
            ->post(route('courses.lesson.complete', [
                'slug' => $product->slug,
                'lessonSlug' => $lessons[0]->slug,
            ]))
            ->assertForbidden();

        app(OrderService::class)->enrollUser($user, $course, null, CourseEnrollment::SOURCE_PURCHASE);

        $this->assertTrue($user->fresh()->isEnrolledIn($course));

        $this->actingAs($user)
            ->from(route('courses.learn', $product->slug))
            ->post(route('courses.lesson.complete', [
                'slug' => $product->slug,
                'lessonSlug' => $lessons[0]->slug,
            ]))
            ->assertRedirect()
            ->assertSessionHas('success', 'درس تکمیل شد.');

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lessons[0]->id,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lessons
     * @return array{0: ShopProduct, 1: Course, 2: array<int, CourseLesson>}
     */
    private function courseWithLessons(array $lessons = []): array
    {
        if ($lessons === []) {
            $lessons = [['slug' => 'lesson-one']];
        }

        $product = ShopProduct::factory()->course()->create();
        $course = Course::factory()->create([
            'shop_product_id' => $product->id,
            'instructor_id' => null,
        ]);
        $section = CourseSection::factory()->create([
            'course_id' => $course->id,
            'sort_order' => 1,
        ]);

        $created = [];
        foreach ($lessons as $index => $attributes) {
            $created[] = CourseLesson::factory()->create(array_merge([
                'section_id' => $section->id,
                'slug' => 'lesson-'.($index + 1),
                'sort_order' => $index + 1,
            ], $attributes));
        }

        return [$product->fresh(), $course, $created];
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

    private function enrollment(User $user, Course $course): CourseEnrollment
    {
        return CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }
}
