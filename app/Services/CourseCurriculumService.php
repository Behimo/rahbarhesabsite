<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;

class CourseCurriculumService
{
    /** @param array<string, mixed> $data */
    public function createSection(Course $course, array $data): CourseSection
    {
        return $course->sections()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function updateSection(CourseSection $section, array $data): void
    {
        $section->update($data);
    }

    public function deleteSection(CourseSection $section): void
    {
        $section->delete();
    }

    /** @param array<string, mixed> $data */
    public function createLesson(CourseSection $section, array $data): CourseLesson
    {
        return $section->lessons()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function updateLesson(CourseLesson $lesson, array $data): void
    {
        $lesson->update($data);
    }

    public function deleteLesson(CourseLesson $lesson): void
    {
        $lesson->delete();
    }
}
