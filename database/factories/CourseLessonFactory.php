<?php

namespace Database\Factories;

use App\Models\CourseLesson;
use App\Models\CourseSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CourseLesson>
 */
class CourseLessonFactory extends Factory
{
    protected $model = CourseLesson::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'section_id' => CourseSection::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'video_provider' => 'aparat',
            'duration_seconds' => fake()->numberBetween(300, 1500),
            'is_free_preview' => false,
            'sort_order' => fake()->numberBetween(1, 30),
        ];
    }

    public function preview(): static
    {
        return $this->state(fn () => [
            'is_free_preview' => true,
        ]);
    }
}
