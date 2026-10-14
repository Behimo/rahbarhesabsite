<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\LessonProgress;
use App\Models\User;

class CourseProgressService
{
    public function update(User $user, Course $course): void
    {
        $lessonIds = CourseLesson::query()
            ->whereIn('section_id', $course->sections()->pluck('id'))
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return;
        }

        $completed = LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->count();

        $percent = (int) round(($completed / $lessonIds->count()) * 100);

        $enrollment = CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (! $enrollment) {
            return;
        }

        $enrollment->update([
            'progress_percent' => $percent,
            'completed_at' => $percent >= 100 ? now() : null,
        ]);
    }
}
