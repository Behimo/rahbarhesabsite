<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\User;
use App\Services\SpotPlayerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IssueSpotplayerLicenseJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public int $courseId,
        public ?int $orderId = null,
    ) {}

    public function handle(SpotPlayerService $spotPlayer): void
    {
        $user = User::query()->find($this->userId);
        $course = Course::query()->find($this->courseId);

        if ($user && $course) {
            $spotPlayer->issueLicense($user, $course, $this->orderId);
        }
    }
}
