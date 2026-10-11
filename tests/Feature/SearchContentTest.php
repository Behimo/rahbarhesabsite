<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_published_courses_posts_and_pages_only(): void
    {
        ShopProduct::factory()->course()->create([
            'title' => 'zq-live دوره مالیات',
            'is_published' => true,
        ]);
        ShopProduct::factory()->course()->create([
            'title' => 'zq-live دوره پیش‌نویس',
            'is_published' => false,
        ]);

        CmsPost::factory()->published()->create([
            'title' => 'zq-live مقاله اظهارنامه',
            'slug' => 'zq-live-post',
        ]);
        CmsPost::factory()->draft()->create([
            'title' => 'zq-live مقاله پیش‌نویس',
            'slug' => 'zq-hidden-post',
        ]);
        CmsPost::factory()->published()->create([
            'title' => 'zq-live مقاله زمان‌بندی‌شده',
            'slug' => 'zq-future-post',
            'published_at' => now()->addDay(),
        ]);

        $this->page('zq-live صفحه درباره آموزش', 'zq-live-page', true);
        $this->page('zq-live صفحه پیش‌نویس', 'zq-hidden-page', false);

        $this->get(route('search', ['q' => 'zq-live']))
            ->assertOk()
            ->assertSee('دوره مالیات', false)
            ->assertSee('مقاله اظهارنامه', false)
            ->assertSee('صفحه درباره آموزش', false)
            ->assertDontSee('دوره پیش‌نویس', false)
            ->assertDontSee('مقاله پیش‌نویس', false)
            ->assertDontSee('مقاله زمان‌بندی‌شده', false)
            ->assertDontSee('صفحه پیش‌نویس', false);
    }

    public function test_empty_search_does_not_list_content(): void
    {
        ShopProduct::factory()->course()->create(['title' => 'zq-empty دوره']);
        CmsPost::factory()->published()->create(['title' => 'zq-empty مقاله', 'slug' => 'zq-empty-post']);
        $this->page('zq-empty صفحه', 'zq-empty-page', true);

        $this->get(route('search'))
            ->assertOk()
            ->assertDontSee('zq-empty دوره', false)
            ->assertDontSee('zq-empty مقاله', false)
            ->assertDontSee('zq-empty صفحه', false);
    }

    private function page(string $title, string $slug, bool $published): void
    {
        CmsPage::query()->create([
            'slug' => $slug,
            'title' => $title,
            'template' => 'content',
            'is_system' => false,
            'is_published' => $published,
            'status' => $published ? 'published' : 'draft',
            'published_at' => $published ? now()->subDay() : null,
        ]);
    }
}
