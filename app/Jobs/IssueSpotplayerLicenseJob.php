<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\SpotplayerLicense;
use App\Models\User;
use App\Services\SpotPlayerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class IssueSpotplayerLicenseJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $userId,
        public int $courseId,
        public ?int $orderId = null,
    ) {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->userId.':'.$this->courseId))->expireAfter(120),
        ];
    }

    public function handle(SpotPlayerService $spotPlayer): void
    {
        $context = $this->logContext();

        Log::channel('jobs')->info('IssueSpotplayerLicenseJob: شروع', $context);

        $user = User::query()->find($this->userId);
        $course = Course::query()->find($this->courseId);

        if (! $user || ! $course) {
            Log::channel('jobs')->error('IssueSpotplayerLicenseJob: کاربر یا دوره پیدا نشد', $context + [
                'user_found' => (bool) $user,
                'course_found' => (bool) $course,
            ]);

            return;
        }

        if (! $spotPlayer->isConfigured()) {
            Log::channel('jobs')->error('IssueSpotplayerLicenseJob: SpotPlayer تنظیم نشده (SPOTPLAYER_API_KEY خالی است)', $context);
            $spotPlayer->issueLicense($user, $course, $this->orderId);

            return;
        }

        if (! $course->spotplayer_course_id) {
            Log::channel('jobs')->error('IssueSpotplayerLicenseJob: دوره spotplayer_course_id ندارد', $context + [
                'course_title' => $course->product?->title,
            ]);
            $spotPlayer->issueLicense($user, $course, $this->orderId);

            return;
        }

        $license = $spotPlayer->issueLicense($user, $course, $this->orderId);

        if ($license->status !== SpotplayerLicense::STATUS_ISSUED) {
            Log::channel('jobs')->warning('IssueSpotplayerLicenseJob: صدور ناموفق؛ تلاش مجدد', $context + [
                'license_id' => $license->id,
                'license_status' => $license->status,
                'api_response' => $license->api_response,
                'attempt' => $this->attempts(),
            ]);

            throw new \RuntimeException(
                'صدور لایسنس اسپات‌پلیر ناموفق بود. status='.$license->status
                .' license_id='.$license->id
            );
        }

        Log::channel('jobs')->info('IssueSpotplayerLicenseJob: موفق', $context + [
            'license_id' => $license->id,
            'license_key' => $license->license_key,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('jobs')->error('IssueSpotplayerLicenseJob: شکست نهایی پس از تمام تلاش‌ها', $this->logContext() + [
            'error' => $exception?->getMessage(),
            'exception' => $exception ? $exception::class : null,
        ]);
    }

    private function logContext(): array
    {
        return [
            'job' => self::class,
            'user_id' => $this->userId,
            'course_id' => $this->courseId,
            'order_id' => $this->orderId,
            'attempt' => $this->job ? $this->attempts() : null,
        ];
    }
}
