<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('panel.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_courses_page_is_accessible(): void
    {
        $product = ShopProduct::query()->create([
            'slug' => 'test-course',
            'title' => 'Test Course',
            'price' => 0,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);

        Course::query()->create([
            'shop_product_id' => $product->id,
            'level' => 'beginner',
        ]);

        $this->get(route('courses.index'))->assertOk();
        $this->get(route('courses.show', 'test-course'))->assertOk();
    }

    public function test_free_course_enrollment(): void
    {
        $user = User::factory()->create();
        $product = ShopProduct::query()->create([
            'slug' => 'free-course',
            'title' => 'Free Course',
            'price' => 0,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);

        $course = Course::query()->create([
            'shop_product_id' => $product->id,
            'level' => 'beginner',
        ]);

        $this->actingAs($user)
            ->get(route('courses.enroll-free', 'free-course'))
            ->assertRedirect(route('courses.learn', 'free-course'));

        $this->assertTrue($user->isEnrolledIn($course));
    }

    public function test_cart_add_product(): void
    {
        $product = ShopProduct::query()->create([
            'slug' => 'paid-course',
            'title' => 'Paid Course',
            'price' => 100000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);

        Course::query()->create([
            'shop_product_id' => $product->id,
            'level' => 'beginner',
        ]);

        $this->post(route('cart.add', $product))
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'shop_product_id' => $product->id,
        ]);
    }
}
