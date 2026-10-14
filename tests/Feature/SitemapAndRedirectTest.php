<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsProduct;
use App\Models\CmsRedirect;
use App\Models\ShopProduct;
use App\Services\SeoService;
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
            ->assertDontSee(route('products.show', 'live-sitemap-product'), false)
            ->assertDontSee(route('products.index'), false);
    }

    public function test_robots_and_non_production_responses_block_indexing(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertSee('Disallow: /', false)
            ->assertDontSee('127.0.0.1', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_search_is_noindex_and_canonical_omits_query_parameters(): void
    {
        $this->get('/search?q=tax&utm_source=campaign')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<link rel="canonical" href="'.rtrim(config('app.url'), '/').'/search">', false);
    }

    public function test_sitemap_does_not_fabricate_lastmod_dates(): void
    {
        $response = $this->get(route('sitemap.products'))->assertOk();

        $this->assertStringNotContainsString('<lastmod>', $response->getContent());

        $this->get(route('sitemap.index'))
            ->assertOk()
            ->assertDontSee('<lastmod>', false);
    }

    public function test_course_schema_converts_toman_amount_to_rials(): void
    {
        $product = ShopProduct::factory()->course()->create([
            'price' => 125000,
            'sale_price' => 99000,
        ]);

        $schema = app(SeoService::class)->courseSchema($product);

        $this->assertSame(990000, $schema['offers']['price']);
        $this->assertSame('IRR', $schema['offers']['priceCurrency']);
    }

    public function test_faq_schema_omits_empty_or_incomplete_faqs(): void
    {
        $seo = app(SeoService::class);

        $this->assertNull($seo->faqSchema([['q' => 'بدون پاسخ', 'a' => '']]));
        $schema = $seo->faqSchema([
            ['q' => 'پرسش معتبر', 'a' => 'پاسخ معتبر'],
            ['q' => '', 'a' => 'پاسخ بدون پرسش'],
        ]);

        $this->assertCount(1, $schema['mainEntity']);
        $this->assertSame('پرسش معتبر', $schema['mainEntity'][0]['name']);
    }

    public function test_article_schema_contains_real_modification_date_and_image_when_available(): void
    {
        $post = CmsPost::factory()->published()->create([
            'slug' => 'schema-article',
            'featured_image' => '/storage/articles/cover.webp',
        ]);

        $this->get(route('blog.show', $post->slug))
            ->assertOk()
            ->assertSee('"dateModified":"'.$post->updated_at->toIso8601String().'"', false)
            ->assertSee('twitter:image', false);
    }
}
