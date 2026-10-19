<?php

namespace App\Services;

use App\Jobs\IssueSpotplayerLicenseJob;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\SpotplayerLicense;
use App\Models\User;

class CourseEnrollmentService
{
    public function enroll(
        User $user,
        Course $course,
        ?Order $order = null,
        string $source = CourseEnrollment::SOURCE_PURCHASE,
    ): CourseEnrollment {
        $months = (int) config('cms.enrollment_months', 12);
        $enrollment = CourseEnrollment::query()->firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'order_id' => $order?->id,
                'enrolled_at' => now(),
                'expires_at' => $months > 0 ? now()->addMonths($months) : null,
                'status' => CourseEnrollment::STATUS_ACTIVE,
                'source' => $source,
            ]
        );

        $needsLicense = $enrollment->wasRecentlyCreated || ! $enrollment->isActive();
        if (! $enrollment->wasRecentlyCreated && ! $enrollment->isActive()) {
            $enrollment->update([
                'status' => CourseEnrollment::STATUS_ACTIVE,
                'order_id' => $order?->id ?? $enrollment->order_id,
                'expires_at' => $months > 0 ? now()->addMonths($months) : null,
                'source' => $source,
            ]);
        }

        if ($needsLicense && $course->spotplayer_course_id) {
            IssueSpotplayerLicenseJob::dispatch($user->id, $course->id, $order?->id)->afterCommit();
        }

        return $enrollment;
    }

    public function enrollImported(User $user, Course $course, Order $order): CourseEnrollment
    {
        $months = (int) config('cms.enrollment_months', 12);

        return CourseEnrollment::query()->firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'order_id' => $order->id,
                'enrolled_at' => now(),
                'expires_at' => $months > 0 ? now()->addMonths($months) : null,
                'status' => CourseEnrollment::STATUS_ACTIVE,
                'source' => CourseEnrollment::SOURCE_PURCHASE,
            ]
        );
    }

    public function revoke(CourseEnrollment $enrollment): void
    {
        $enrollment->update([
            'status' => CourseEnrollment::STATUS_REVOKED,
            'expires_at' => now(),
        ]);

        app(SpotPlayerService::class)->revokeForCourse((int) $enrollment->user_id, (int) $enrollment->course_id);
    }

    public function retryLicenses(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            foreach ($product->relatedCourses() as $course) {
                if ($course->spotplayer_course_id) {
                    IssueSpotplayerLicenseJob::dispatch($order->user_id, $course->id, $order->id)->afterCommit();
                }
            }
        }
    }
}
