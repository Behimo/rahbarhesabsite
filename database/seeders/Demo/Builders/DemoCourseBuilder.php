<?php

namespace Database\Seeders\Demo\Builders;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\ShopProduct;
use App\Models\User;
use Database\Seeders\Demo\Support\CourseDefinition;
use Database\Seeders\Demo\Support\LessonDefinition;
use Database\Seeders\Demo\Support\SectionDefinition;

final class DemoCourseBuilder
{
    public function build(CourseDefinition $definition, User $instructor): Course
    {
        $product = $this->upsertProduct($definition);
        $course = $this->upsertCourse($definition, $product, $instructor);

        foreach (array_values($definition->sections) as $index => $sectionDefinition) {
            $this->buildSection($course, $sectionDefinition, $index + 1);
        }

        return $course->fresh(['product', 'sections.lessons']);
    }

    private function upsertProduct(CourseDefinition $definition): ShopProduct
    {
        return ShopProduct::query()->updateOrCreate(
            ['slug' => $definition->slug],
            [
                'title' => $definition->title,
                'subtitle' => $definition->subtitle,
                'description' => $definition->description,
                'price' => $definition->price,
                'sale_price' => $definition->salePrice,
                'type' => ShopProduct::TYPE_COURSE,
                'featured_image' => $this->imageUrl($definition->slug),
                'is_published' => true,
                'sort_order' => $definition->sortOrder,
                'meta_title' => $definition->title.' | راهبر حساب',
                'meta_description' => $definition->description,
            ]
        );
    }

    private function upsertCourse(CourseDefinition $definition, ShopProduct $product, User $instructor): Course
    {
        return Course::query()->updateOrCreate(
            ['shop_product_id' => $product->id],
            [
                'instructor_id' => $instructor->id,
                'level' => $definition->level,
                'duration_minutes' => $definition->durationMinutes,
                'what_you_learn' => $definition->whatYouLearn,
                'requirements' => $definition->requirements,
            ]
        );
    }

    private function buildSection(Course $course, SectionDefinition $definition, int $sortOrder): void
    {
        $section = CourseSection::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'title' => $definition->title,
            ],
            ['sort_order' => $sortOrder]
        );

        foreach (array_values($definition->lessons) as $index => $lessonDefinition) {
            $this->buildLesson($section, $lessonDefinition, $index + 1);
        }
    }

    private function buildLesson(CourseSection $section, LessonDefinition $definition, int $sortOrder): void
    {
        CourseLesson::query()->updateOrCreate(
            [
                'section_id' => $section->id,
                'slug' => $definition->slug,
            ],
            [
                'title' => $definition->title,
                'content' => $definition->content,
                'duration_seconds' => $definition->durationSeconds,
                'is_free_preview' => $definition->isFreePreview,
                'video_provider' => 'aparat',
                'sort_order' => $sortOrder,
            ]
        );
    }

    private function imageUrl(string $seed): string
    {
        return 'https://picsum.photos/seed/'.$seed.'/960/540';
    }
}
