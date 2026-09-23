<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'shop_product_id' => ShopProduct::factory()->course(),
            'instructor_id' => User::factory()->instructor(),
            'level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'duration_minutes' => fake()->numberBetween(120, 960),
            'what_you_learn' => [
                fake()->sentence(4),
                fake()->sentence(4),
                fake()->sentence(4),
            ],
            'requirements' => [
                fake()->sentence(5),
            ],
        ];
    }

    public function withCurriculum(int $sections = 2, int $lessonsPerSection = 3): static
    {
        return $this->afterCreating(function (Course $course) use ($sections, $lessonsPerSection) {
            for ($sectionIndex = 1; $sectionIndex <= $sections; $sectionIndex++) {
                $section = CourseSection::factory()->create([
                    'course_id' => $course->id,
                    'title' => 'فصل '.$sectionIndex,
                    'sort_order' => $sectionIndex,
                ]);

                for ($lessonIndex = 1; $lessonIndex <= $lessonsPerSection; $lessonIndex++) {
                    CourseLesson::factory()->create([
                        'section_id' => $section->id,
                        'slug' => 'lesson-'.$sectionIndex.'-'.$lessonIndex,
                        'title' => 'درس '.$lessonIndex.' از فصل '.$sectionIndex,
                        'is_free_preview' => $sectionIndex === 1 && $lessonIndex === 1,
                        'sort_order' => $lessonIndex,
                    ]);
                }
            }
        });
    }
}
