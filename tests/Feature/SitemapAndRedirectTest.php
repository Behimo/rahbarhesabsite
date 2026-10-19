<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsProduct;
use App\Models\CmsRedirect;
use App\Models\ShopProduct;
use App\Services\PermalinkService;
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
            'to_path' => '/blog/',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/old-tax-guide')
            ->assertStatus(301)
            ->assertRedirect('/blog/');
    }

    public function test_query_p_redirect_resolves(): void
    {
        CmsRedirect::query()->create([
            'from_path' => '/?p=42',
            'to_path' => '/blogs/sample-post/',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/?p=42')
            ->assertStatus(301)
            ->assertRedirect('/blogs/sample-post/');
    }

    public function test_inactive_redirect_is_not_followed(): void
    {
        CmsRedirect::query()->create([
            'from_path' => '/retired-guide',
            'to_path' => '/blog/',
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

    public function test_gone_wordpress_paths_return_410(): void
    {
        $this->get('/feed')->assertStatus(410);
        $this->get('/wp-login.php')->assertStatus(410);
    }

    public function test_product_and_blog_parity_routes(): void
    {
        $course = ShopProduct::factory()->course()->create([
            'slug' => 'parity-course',
            'is_published' => true,
        ]);

        $post = CmsPost::factory()->published()->create([
            'slug' => 'parity-post',
        ]);

        $this->get('/product/parity-course')
            ->assertOk()
            ->assertSee($course->title, false);

        $this->get('/blog/parity-post')
            ->assertOk()
            ->assertSee($post->title, false);
    }

    public function test_sitemaps_omit_unpublished_content(): void
    {
        $livePost = CmsPost::factory()->published()->create(['slug' => 'live-sitemap-post']);
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

        $liveCourse = ShopProduct::factory()->course()->create([
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

        $permalinks = app(PermalinkService::class);

        $this->get(route('sitemap.posts'))
            ->assertOk()
            ->assertSee($permalinks->postAbsolute($livePost), false)
            ->assertDontSee('draft-sitemap-post', false);

        $this->get(route('sitemap.pages'))
            ->assertOk()
            ->assertSee($permalinks->absolute('/live-sitemap-page'), false)
            ->assertDontSee('draft-sitemap-page', false);

        $this->get(route('sitemap.courses'))
            ->assertOk()
            ->assertSee($permalinks->productAbsolute($liveCourse), false)
            ->assertDontSee('draft-sitemap-course', false);

        $this->get(route('sitemap.products'))
            ->assertOk()
            ->assertDontSee('live-sitemap-product', false);
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
        $canonical = app(PermalinkService::class)->absolute('/search');

        $this->get('/search?q=tax&utm_source=campaign')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<link rel="canonical" href="'.$canonical.'">', false);
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
        $this->assertStringContainsString('/product/', $schema['url']);
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

        // In tests trailing-slash middleware is skipped; hit the WP-style category/slug path.
        $this->get('/blog/schema-article')
            ->assertOk()
            ->assertSee('"dateModified":"'.$post->updated_at->toIso8601String().'"', false)
            ->assertSee('twitter:image', false);
    }
}
