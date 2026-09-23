<?php

namespace Database\Factories;

use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShopProduct>
 */
class ShopProductFactory extends Factory
{
    protected $model = ShopProduct::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'title' => $title,
            'subtitle' => fake()->optional()->sentence(3),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(500000, 3500000),
            'sale_price' => null,
            'type' => ShopProduct::TYPE_COURSE,
            'featured_image' => 'https://picsum.photos/seed/'.fake()->unique()->uuid().'/960/540',
            'is_published' => true,
            'meta_title' => $title,
            'meta_description' => fake()->sentence(10),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function course(): static
    {
        return $this->state(fn () => [
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'price' => 0,
            'sale_price' => null,
        ]);
    }

    public function onSale(): static
    {
        return $this->state(function (array $attributes) {
            $price = (int) ($attributes['price'] ?? 2000000);

            return [
                'sale_price' => (int) max(100000, $price * 0.8),
            ];
        });
    }
}
