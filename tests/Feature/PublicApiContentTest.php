<?php

namespace Tests\Feature;

use App\Models\CmsPost;
use App\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.api_token' => 'test-token']);
    }

    public function test_posts_and_courses_require_a_token(): void
    {
        $this->getJson('/api/v1/posts')->assertUnauthorized();
        $this->getJson('/api/v1/posts/live-post')->assertUnauthorized();
        $this->getJson('/api/v1/courses')->assertUnauthorized();
        $this->getJson('/api/v1/courses/live-course')->assertUnauthorized();
    }

    public function test_posts_api_returns_only_published_posts(): void
    {
        CmsPost::factory()->published()->create([
            'slug' => 'live-post',
            'title' => 'مقاله منتشرشده',
        ]);
        CmsPost::factory()->draft()->create([
            'slug' => 'draft-post',
            'title' => 'مقاله پیش‌نویس',
        ]);

        $this->withToken('test-token')
            ->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'live-post'])
            ->assertJsonMissing(['slug' => 'draft-post']);

        $this->withToken('test-token')
            ->getJson('/api/v1/posts/live-post')
            ->assertOk()
            ->assertJsonFragment(['title' => 'مقاله منتشرشده']);

        $this->withToken('test-token')
            ->getJson('/api/v1/posts/draft-post')
            ->assertNotFound();
    }

    public function test_courses_api_returns_only_published_courses(): void
    {
        ShopProduct::factory()->course()->create([
            'slug' => 'live-course',
            'title' => 'دوره منتشرشده',
            'is_published' => true,
        ]);
        ShopProduct::factory()->course()->create([
            'slug' => 'draft-course',
            'title' => 'دوره پیش‌نویس',
            'is_published' => false,
        ]);

        $this->withToken('test-token')
            ->getJson('/api/v1/courses')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'live-course'])
            ->assertJsonMissing(['slug' => 'draft-course']);

        $this->withToken('test-token')
            ->getJson('/api/v1/courses/live-course')
            ->assertOk()
            ->assertJsonFragment(['title' => 'دوره منتشرشده']);

        $this->withToken('test-token')
            ->getJson('/api/v1/courses/draft-course')
            ->assertNotFound();
    }
}
