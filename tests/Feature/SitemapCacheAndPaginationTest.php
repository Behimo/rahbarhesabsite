<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use App\Models\Tag;
use App\Services\CacheService;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SitemapCacheAndPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_cache_invalidates_content_but_not_views(): void
    {
        $post = CmsPost::factory()->published()->create(['slug' => 'cache-first']);
        $seo = app(SeoService::class);
        $first = $seo->postSitemapUrls();
        DB::enableQueryLog();
        $this->assertSame($first, $seo->postSitemapUrls());
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        $version = Cache::get('seo.sitemap.version');
        $this->get('/blog/cache-first')->assertOk();
        $this->assertSame($version, Cache::get('seo.sitemap.version'));
        $post->update(['slug' => 'cache-second']);
        $this->assertStringContainsString('cache-second', json_encode($seo->postSitemapUrls()));
        $post->update(['robots' => 'noindex, follow']);
        $this->assertStringNotContainsString('cache-second', json_encode($seo->postSitemapUrls()));
        $post->update(['robots' => 'index, follow']);
        $post->delete();
        $this->assertStringNotContainsString('cache-second', json_encode($seo->postSitemapUrls()));
        $post->restore();
        $this->assertStringContainsString('cache-second', json_encode($seo->postSitemapUrls()));
    }

    public function test_empty_archives_are_omitted_but_populated_ancestors_remain_and_pivots_invalidate(): void
    {
        $parent = Category::factory()->create(['type' => 'post', 'slug' => 'parent', 'is_active' => true]);
        $child = Category::factory()->create(['type' => 'post', 'slug' => 'child', 'parent_id' => $parent->id, 'is_active' => true]);
        $empty = Category::factory()->create(['type' => 'post', 'slug' => 'empty', 'is_active' => true]);
        $tag = Tag::create(['slug' => 'used-tag', 'name' => 'Used']);
        Tag::create(['slug' => 'empty-tag', 'name' => 'Empty']);
        $post = CmsPost::factory()->published()->create();
        $seo = app(SeoService::class);
        $seo->postSitemapUrls();
        $post->syncCategories([$child->id]);
        $post->syncTags([$tag->id]);
        $xml = $this->get('/post-sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('/category/parent/', $xml);
        $this->assertStringContainsString('/category/parent/child/', $xml);
        $this->assertStringContainsString('/tag/used-tag/', $xml);
        $this->assertStringNotContainsString('/category/empty/', $xml);
        $this->assertStringNotContainsString('/tag/empty-tag/', $xml);
        $post->syncCategories([]);
        $post->syncTags([]);
        $xml = $this->get('/post-sitemap.xml')->assertOk()->getContent();
        $this->assertStringNotContainsString('/category/parent/', $xml);
        $this->assertStringNotContainsString('/tag/used-tag/', $xml);
    }

    public function test_sitemap_cache_is_namespaced_and_flush_content_rebuilds_bulk_updates(): void
    {
        $post = CmsPost::factory()->published()->create(['slug' => 'bulk-first']);
        $seo = app(SeoService::class);
        $seo->postSitemapUrls();
        CmsPost::whereKey($post->id)->update(['slug' => 'bulk-second']);
        app(CacheService::class)->flushContent();
        $this->assertStringContainsString('bulk-second', json_encode($seo->postSitemapUrls()));
        config(['app.url' => 'http://another.test:9000']);
        $this->assertStringContainsString('http:\/\/another.test:9000', json_encode($seo->postSitemapUrls()));
    }

    public function test_system_noindex_is_omitted_and_legacy_maps_have_real_contents(): void
    {
        CmsPage::create(['slug' => 'contact', 'title' => 'Contact', 'is_system' => true, 'is_published' => true,
            'status' => 'published', 'robots' => 'noindex, follow']);
        $this->get('/page-sitemap.xml')->assertOk()->assertDontSee('/contact-us/', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('<sitemapindex', false)->assertSee('/course-sitemap.xml', false);
        ShopProduct::factory()->course()->create(['slug' => 'compat-course', 'is_published' => true]);
        $this->get('/product-sitemap.xml')->assertOk()->assertSee('/product/compat-course/', false);
    }

    public function test_out_of_range_archives_return_404_but_empty_page_one_remains_200(): void
    {
        $this->get('/blog')->assertOk();
        $this->get('/product')->assertOk();
        $this->get('/blog/page/999')->assertNotFound();
        $this->get('/product/page/999?type=free')->assertNotFound();
        $tag = Tag::create(['slug' => 'empty-tag', 'name' => 'Empty']);
        $category = Category::factory()->create(['type' => 'product', 'slug' => 'empty-product', 'is_active' => true]);
        $this->get('/tag/empty-tag')->assertOk();
        $this->get('/tag/empty-tag/page/2')->assertNotFound();
        $this->get('/product-category/empty-product/page/2')->assertNotFound();
    }
}
