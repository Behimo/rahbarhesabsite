<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'type' => Category::TYPE_PRODUCT,
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->optional()->sentence(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function product(): static
    {
        return $this->state(fn () => [
            'type' => Category::TYPE_PRODUCT,
        ]);
    }

    public function post(): static
    {
        return $this->state(fn () => [
            'type' => Category::TYPE_POST,
        ]);
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn () => [
            'type' => $parent->type,
            'parent_id' => $parent->id,
        ]);
    }
}
