<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsProduct;
use App\Models\CmsRedirect;
use App\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapAndRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_redirect_sends_visitors_to_the_target(): void
    {
        CmsRedirect::query()->create([
            'from_path' => '/old-tax-guide',
            'to_path' => '/blog',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/old-tax-guide')
            ->assertStatus(301)
            ->assertRedirect('/blog');
    }

    public function test_inactive_redirect_is_not_followed(): void
    {
        CmsRedirect::query()->create([
            'from_path' => '/retired-guide',
            'to_path' => '/blog',
            'status_code' => 301,
            'is_active' => false,
        ]);

        $this->get('/retired-guide')->assertNotFound();
    }

    public function test_redirect_with_an_unsafe_target_is_not_followed(): void
    {
        CmsRedirect::query()->create([
            'from_path' => '/external-hop',
            'to_path' => '//evil.example',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/external-hop')->assertNotFound();
    }

    public function test_sitemaps_omit_unpublished_content(): void
    {
        CmsPost::factory()->published()->create(['slug' => 'live-sitemap-post']);
        CmsPost::factory()->draft()->create(['slug' => 'draft-sitemap-post']);

        CmsPage::query()->create([
            'slug' => 'live-sitemap-page',
            'title' => 'صفحه منتشرشده',
            'template' => 'content',
            'is_system' => false,
            'is_published' => true,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        CmsPage::query()->create([
            'slug' => 'draft-sitemap-page',
            'title' => 'صفحه پیش‌نویس',
            'template' => 'content',
            'is_system' => false,
            'is_published' => false,
            'status' => 'draft',
        ]);

        ShopProduct::factory()->course()->create([
            'slug' => 'live-sitemap-course',
            'is_published' => true,
        ]);
        ShopProduct::factory()->course()->create([
            'slug' => 'draft-sitemap-course',
            'is_published' => false,
        ]);

        CmsProduct::query()->create([
            'slug' => 'live-sitemap-product',
            'title' => 'محصول منتشرشده',
            'is_published' => true,
        ]);
        CmsProduct::query()->create([
            'slug' => 'draft-sitemap-product',
            'title' => 'محصول پیش‌نویس',
            'is_published' => false,
        ]);

        $this->get(route('sitemap.posts'))
            ->assertOk()
            ->assertSee(route('blog.show', 'live-sitemap-post'), false)
            ->assertDontSee('draft-sitemap-post', false);

        $this->get(route('sitemap.pages'))
            ->assertOk()
            ->assertSee(route('pages.show', 'live-sitemap-page'), false)
            ->assertDontSee('draft-sitemap-page', false);

        $this->get(route('sitemap.courses'))
            ->assertOk()
            ->assertSee(route('courses.show', 'live-sitemap-course'), false)
            ->assertDontSee('draft-sitemap-course', false);

        $this->get(route('sitemap.products'))
            ->assertOk()
            ->assertSee(route('products.show', 'live-sitemap-product'), false)
            ->assertDontSee('draft-sitemap-product', false);
    }
}
