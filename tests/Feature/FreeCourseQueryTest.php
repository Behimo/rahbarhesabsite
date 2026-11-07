<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreeCourseQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_zero_sale_price_is_free_on_the_home_page_and_the_catalog(): void
    {
        $free = $this->course('دوره حراج صفر', 180000, 0);
        $paid = $this->course('دوره پولی مشخص', 90000, null);

        $home = $this->get(route('home'));
        $home->assertOk();
        $this->assertMatchesRegularExpression('/id="freeCourses"[\\s\\S]*دوره حراج صفر/u', $home->getContent());

        $this->get(route('courses.index', ['type' => 'free']))
            ->assertOk()
            ->assertSee('دوره حراج صفر')
            ->assertDontSee('دوره پولی مشخص');

        $this->get(route('courses.index', ['type' => 'paid']))
            ->assertOk()
            ->assertSee('دوره پولی مشخص')
            ->assertDontSee('دوره حراج صفر');

        $this->assertTrue($free->isFree());
        $this->assertFalse($paid->isFree());
    }

    private function course(string $title, int $price, ?int $salePrice): ShopProduct
    {
        $product = ShopProduct::query()->create([
            'slug' => 'price-'.uniqid(),
            'title' => $title,
            'price' => $price,
            'sale_price' => $salePrice,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);
        Course::query()->create([
            'shop_product_id' => $product->id,
            'level' => 'beginner',
        ]);

        return $product;
    }
}
