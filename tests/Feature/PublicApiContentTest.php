<?php

namespace Tests\Feature;

use App\Models\CmsPage;
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

    public function test_all_public_content_endpoints_require_the_configured_token(): void
    {
        foreach (['pages', 'posts', 'courses'] as $resource) {
            $this->getJson('/api/v1/'.$resource)->assertUnauthorized();
            $this->getJson('/api/v1/'.$resource.'/some-slug')->assertUnauthorized();
            $this->withToken('wrong-token')->getJson('/api/v1/'.$resource)->assertUnauthorized();
        }
    }

    public function test_pages_api_returns_only_published_pages(): void
    {
        CmsPage::query()->create([
            'slug' => 'public-page', 'title' => 'صفحه منتشرشده',
            'template' => 'content', 'is_system' => false,
            'is_published' => true, 'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        CmsPage::query()->create([
            'slug' => 'private-page', 'title' => 'صفحه پیش‌نویس',
            'template' => 'content', 'is_system' => false,
            'is_published' => false, 'status' => 'draft',
        ]);

        $this->withToken('test-token')->getJson('/api/v1/pages')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'public-page'])
            ->assertJsonMissing(['slug' => 'private-page']);
        $this->withToken('test-token')->getJson('/api/v1/pages/public-page')
            ->assertOk()->assertJsonFragment(['slug' => 'public-page']);
        $this->withToken('test-token')->getJson('/api/v1/pages/private-page')
            ->assertNotFound();
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
