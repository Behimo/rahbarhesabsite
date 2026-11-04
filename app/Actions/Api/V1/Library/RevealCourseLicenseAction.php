<?php

namespace App\Actions\Api\V1\Library;

use App\Exceptions\Api\ApiException;
use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\Course;
use App\Models\SpotplayerLicense;
use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Support\Facades\Cache;

class RevealCourseLicenseAction
{
    public function handle(User $user, Course $course, ?string $ip): SpotplayerLicense
    {
        if (! $user->isEnrolledIn($course)) {
            throw new ApiException('این دوره برای شما فعال نیست.', 'course_not_owned', 404);
        }

        $license = SpotplayerLicense::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($license && $license->status === SpotplayerLicense::STATUS_REVOKED) {
            throw new ApiException('لایسنس این دوره باطل شده است.', 'license_revoked', 403);
        }

        if (! $license || $license->status !== SpotplayerLicense::STATUS_ISSUED || ! $license->license_key) {
            $this->retryOnce($user, $course, $license);

            throw new ApiException('لایسنس در حال صدور است.', 'license_pending', 202);
        }

        AuditLog::record('app.license.viewed', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'ip' => $ip,
        ]);

        return $license;
    }

    private function retryOnce(User $user, Course $course, ?SpotplayerLicense $license): void
    {
        if (! $course->spotplayer_course_id) {
            throw new ApiException('برای این دوره لایسنس پخشی تعریف نشده است.', 'license_unavailable', 404);
        }

        if ($license && $license->status === SpotplayerLicense::STATUS_PENDING) {
            return;
        }

        $claimed = Cache::add('app:license-retry:'.$user->id.':'.$course->id, true, now()->addMinutes(2));
        if (! $claimed) {
            return;
        }

        IssueSpotplayerLicenseJob::dispatch($user->id, $course->id, $license?->order_id)->afterCommit();
    }
}
