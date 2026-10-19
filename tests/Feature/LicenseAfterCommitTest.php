<?php

namespace Tests\Feature;

use App\Models\CourseEnrollment;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\CourseEnrollmentService;
use App\Services\SpotPlayerService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use RuntimeException;
use Tests\TestCase;

class LicenseAfterCommitTest extends TestCase
{
    use DatabaseMigrations;

    #[RunInSeparateProcess]
    public function test_rolled_back_enrollment_never_runs_a_license_job_on_the_real_sync_queue(): void
    {
        config([
            'queue.default' => 'sync',
            'cms.spotplayer.api_key' => 'test-only',
        ]);
        Http::preventStrayRequests();
        $spotPlayer = $this->mock(SpotPlayerService::class);
        $spotPlayer->shouldReceive('isConfigured')->never();
        $spotPlayer->shouldReceive('issueLicense')->never();

        $user = User::factory()->create();
        $product = ShopProduct::factory()->course()->create();
        $course = $product->course()->create([
            'level' => 'beginner',
            'spotplayer_course_id' => 'remote-test-course',
        ]);

        try {
            DB::transaction(function () use ($user, $course): void {
                app(CourseEnrollmentService::class)->enroll($user, $course, null, CourseEnrollment::SOURCE_FREE);
                throw new RuntimeException('roll back enrollment');
            });
            $this->fail('The transaction should have rolled back.');
        } catch (RuntimeException $exception) {
            $this->assertSame('roll back enrollment', $exception->getMessage());
        }

        $this->assertDatabaseMissing('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
        $this->assertDatabaseMissing('spotplayer_licenses', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }
}
