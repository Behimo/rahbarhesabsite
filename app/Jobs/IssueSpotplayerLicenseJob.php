<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\SpotplayerLicense;
use App\Models\User;
use App\Services\SpotPlayerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

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
        $user = User::query()->find($this->userId);
        $course = Course::query()->find($this->courseId);

        if (! $user || ! $course) {
            return;
        }

        if (! $spotPlayer->isConfigured() || ! $course->spotplayer_course_id) {
            return;
        }

        $license = $spotPlayer->issueLicense($user, $course, $this->orderId);

        if ($license->status !== SpotplayerLicense::STATUS_ISSUED) {
            throw new \RuntimeException('صدور لایسنس اسپات‌پلیر ناموفق بود.');
        }
    }
}
