<?php

namespace Database\Factories;

use App\Models\CmsCategory;
use App\Models\CmsPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CmsPost>
 */
class CmsPostFactory extends Factory
{
    protected $model = CmsPost::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'category_id' => CmsCategory::factory(),
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'title' => $title,
            'excerpt' => fake()->sentence(12),
            'body' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'featured_image' => 'https://picsum.photos/seed/'.fake()->unique()->uuid().'/960/540',
            'featured_image_alt' => fake()->optional()->sentence(3),
            'author' => fake()->name(),
            'meta_title' => $title,
            'meta_description' => fake()->sentence(10),
            'is_published' => true,
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(1, 40)),
            'views' => fake()->numberBetween(20, 2500),
            'reading_time_minutes' => fake()->numberBetween(2, 12),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'is_published' => true,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_published' => false,
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
