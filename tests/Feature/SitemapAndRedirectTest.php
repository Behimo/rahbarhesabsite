<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsProduct;
use App\Models\CmsRedirect;
use App\Models\ShopProduct;
use App\Models\Tag;
use App\Services\PermalinkService;
use App\Services\RankMathRedirectImporter;
use App\Services\SeoService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapAndRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_cms_redirect_rows_are_executed_on_public_requests(): void
    {
        CmsRedirect::query()->create([
            'from_path' => '/old-tax-guide',
            'to_path' => '/blog/',
            'status_code' => 301,
            'is_active' => true,
            'from_query' => '',
            'match_query' => false,
        ]);

        // Historical redirects must lead directly to the final WordPress URL.
        $this->get('/old-tax-guide')->assertRedirect(app(PermalinkService::class)->absolute('/blog/'));
    }

    public function test_query_p_redirect_rows_are_executed_on_public_requests(): void
    {
        CmsRedirect::query()->create([
            'from_path' => '/?p=42',
            'to_path' => '/blogs/sample-post/',
            'status_code' => 301,
            'is_active' => true,
            'from_query' => '',
            'match_query' => false,
        ]);

        $this->get('/?p=42')->assertRedirect(app(PermalinkService::class)->absolute('/blogs/sample-post/'));
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

        // Nonproduction requests are not forced onto the production host or scheme.
        $this->get('/blog/schema-article')
            ->assertOk()
            ->assertSee('"dateModified":"'.$post->updated_at->toIso8601String().'"', false)
            ->assertSee('twitter:image', false);
    }

    public function test_home_news_links_use_post_permalink(): void
    {
        $category = Category::query()->create([
            'type' => Category::TYPE_POST,
            'name' => 'اخبار',
            'slug' => 'news',
            'is_active' => true,
        ]);

        $post = CmsPost::factory()->published()->create([
            'slug' => 'home-news-post',
            'title' => 'خبر صفحه اصلی',
        ]);
        $post->syncCategories([$category->id], $category->id);

        $url = app(PermalinkService::class)->postAbsolute($post->fresh(['categories']));

        $this->get('/')
            ->assertOk()
            ->assertSee('خبر صفحه اصلی', false)
            ->assertSee('href="'.$url.'"', false);

        // The category-based permalink (/{category}/{slug}) must resolve too.
        $this->get('/news/home-news-post')->assertOk()->assertSee('خبر صفحه اصلی', false);
    }

    public function test_blog_and_product_paged_and_tag_archives_resolve(): void
    {
        Tag::query()->create(['name' => 'مالیات', 'slug' => 'maliat']);

        for ($i = 0; $i <= config('seo.per_page.posts'); $i++) {
            CmsPost::factory()->published()->create(['slug' => 'paged-post-'.$i]);
        }

        for ($i = 0; $i <= config('seo.per_page.products'); $i++) {
            ShopProduct::factory()->course()->create([
                'slug' => 'paged-course-'.$i,
                'is_published' => true,
            ]);
        }

        $this->get('/blog/page/2')->assertOk();
        $this->get('/tag/maliat')->assertOk();
        $this->get('/product/page/2')->assertOk();
    }

    public function test_nested_category_archives_resolve(): void
    {
        $postParent = Category::query()->create([
            'type' => Category::TYPE_POST,
            'name' => 'آموزش',
            'slug' => 'learn',
            'is_active' => true,
        ]);
        $postChild = Category::query()->create([
            'type' => Category::TYPE_POST,
            'parent_id' => $postParent->id,
            'name' => 'مالیات',
            'slug' => 'tax',
            'is_active' => true,
        ]);

        $productParent = Category::query()->create([
            'type' => Category::TYPE_PRODUCT,
            'name' => 'نرم‌افزار',
            'slug' => 'software',
            'is_active' => true,
        ]);
        $productChild = Category::query()->create([
            'type' => Category::TYPE_PRODUCT,
            'parent_id' => $productParent->id,
            'name' => 'حسابداری',
            'slug' => 'accounting',
            'is_active' => true,
        ]);

        $this->assertSame('learn/tax', $postChild->fresh()->full_slug);
        $this->assertSame('software/accounting', $productChild->fresh()->full_slug);

        $this->get('/category/learn/tax')->assertOk();
        $this->get('/product-category/software/accounting')->assertOk();
    }

    public function test_rankmath_query_redirect_import_is_executed(): void
    {
        $importer = app(RankMathRedirectImporter::class);
        $result = $importer->import("/old-page?utm=1,/blog/,301\n");

        $this->assertSame(1, $result['imported']);

        $row = CmsRedirect::query()->where('from_path', '/old-page')->where('from_query', 'utm=1')->first();
        $this->assertNotNull($row);
        $this->assertTrue($row->match_query);

        $this->get('/old-page?utm=1')->assertRedirect(app(PermalinkService::class)->absolute('/blog/'));
    }

    public function test_course_pagination_preserves_type_filter(): void
    {
        for ($i = 0; $i <= config('seo.per_page.products'); $i++) {
            ShopProduct::factory()->course()->create([
                'slug' => 'free-course-'.$i,
                'price' => 0,
                'sale_price' => 0,
                'is_published' => true,
            ]);
        }

        $html = $this->get('/product?type=free')->assertOk()->getContent();

        $this->assertStringContainsString('/product/page/2/', $html);
        $this->assertStringContainsString('type=free', $html);
    }

    public function test_page_slug_starting_with_reserved_word_still_resolves(): void
    {
        foreach (['builder-page', 'productivity', 'blogging', 'site-map-guide'] as $slug) {
            CmsPage::query()->create([
                'slug' => $slug,
                'title' => 'صفحه '.$slug,
                'template' => 'content',
                'is_system' => false,
                'is_published' => true,
                'status' => 'published',
            ]);

            $this->get('/'.$slug)->assertOk();
        }
    }

    public function test_reserved_exact_slug_is_not_routed_as_a_page(): void
    {
        CmsPage::query()->create([
            'slug' => 'build',
            'title' => 'صفحه رزرو شده',
            'template' => 'content',
            'is_system' => false,
            'is_published' => true,
            'status' => 'published',
        ]);

        $this->get('/build')->assertNotFound();
    }

    public function test_external_redirect_target_is_executed(): void
    {
        $importer = app(RankMathRedirectImporter::class);
        $result = $importer->import("/go,https://example.com/landing,301\n");

        $this->assertSame(1, $result['imported']);
        $this->assertSame('https://example.com/landing', CmsRedirect::query()->value('to_path'));

        $this->get('/go')->assertRedirect('https://example.com/landing');
    }

    public function test_self_redirect_differing_only_by_trailing_slash_is_skipped(): void
    {
        $importer = app(RankMathRedirectImporter::class);
        $result = $importer->import("/same-page,/same-page/,301\n");

        $this->assertSame(0, $result['imported']);
        $this->assertSame(0, CmsRedirect::query()->count());

        // No 301 loop — the path falls through to the (missing) page.
        $this->get('/same-page')->assertNotFound();
    }

    public function test_query_redirect_rows_match_with_extra_params(): void
    {
        $importer = app(RankMathRedirectImporter::class);
        $importer->import("/old-page?utm=1,/blog/,301\n");

        $this->get('/old-page?utm=1&gclid=abc')->assertRedirect(app(PermalinkService::class)->absolute('/blog/'));
    }

    public function test_duplicate_redirect_path_is_rejected_by_composite_unique(): void
    {
        CmsRedirect::query()->create([
            'from_path' => '/dupe',
            'from_query' => '',
            'to_path' => '/a/',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        CmsRedirect::query()->create([
            'from_path' => '/dupe',
            'from_query' => '',
            'to_path' => '/b/',
            'status_code' => 301,
            'is_active' => true,
        ]);
    }
}
