<?php

namespace Database\Factories;

use App\Models\CmsCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CmsCategory>
 */
class CmsCategoryFactory extends Factory
{
    protected $model = CmsCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'name' => $name,
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }
}
