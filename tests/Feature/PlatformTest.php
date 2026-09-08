<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_otp(): void
    {
        $phone = '09123456789';

        $this->post('/login/otp', ['phone' => $phone, 'name' => 'Test User'])
            ->assertRedirect(route('login.verify'));

        $code = app(OtpService::class)->peekLatestCode($phone);
        $this->assertNotNull($code);

        $this->post('/login/verify', ['code' => $code])
            ->assertRedirect(route('panel.dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['phone' => '09123456789']);
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
        $user = User::factory()->create(['phone' => '09121111111']);
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

    public function test_sitemap_index_is_accessible(): void
    {
        $this->get('/sitemap_index.xml')->assertOk();
        $this->get('/course-sitemap.xml')->assertOk();
    }
}
