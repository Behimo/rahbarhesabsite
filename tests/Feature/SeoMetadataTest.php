<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use App\Services\SeoService;
use App\Support\RobotsDirectives;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_views_do_not_change_modification_date_or_sitemap_lastmod(): void
    {
        $post = CmsPost::factory()->published()->create(['slug' => 'stable-date', 'updated_at' => '2020-01-01 00:00:00', 'views' => 0]);
        $before = $post->updated_at->toAtomString();
        $this->get('/blog/stable-date')->assertOk()->assertSee('"dateModified":"'.$before.'"', false)
            ->assertSee('<meta property="og:type" content="article">', false);
        $this->get('/blog/stable-date')->assertOk()->assertSee('"dateModified":"'.$before.'"', false);
        $this->assertSame($before, $post->fresh()->updated_at->toAtomString());
        $this->assertSame(2, $post->fresh()->views);
        $this->get('/post-sitemap.xml')->assertOk()->assertSee('<lastmod>'.$before.'</lastmod>', false);
    }

    public function test_home_uses_cms_metadata_and_does_not_query_it_twice(): void
    {
        CmsPage::create(['slug' => 'home', 'title' => 'Home', 'meta_title' => 'ADMIN SEO TITLE',
            'meta_description' => 'ADMIN SEO DESCRIPTION', 'is_published' => true, 'status' => 'published',
            'is_system' => true, 'template' => 'home', 'robots' => 'index, follow',
            'canonical_url' => 'https://example.com/custom-home/']);
        DB::enableQueryLog();
        $this->get('/')->assertOk()->assertSee('<title>ADMIN SEO TITLE</title>', false)
            ->assertSee('content="ADMIN SEO DESCRIPTION"', false)
            ->assertSee('href="https://example.com/custom-home/"', false);
        $queries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], '"cms_pages"')
            && str_contains($query['query'], '"slug" = ?') && in_array('home', $query['bindings'], true));
        $this->assertCount(1, $queries);
        DB::disableQueryLog();
    }

    public function test_query_noindex_cannot_be_overridden_by_content_in_production(): void
    {
        $post = CmsPost::factory()->published()->create(['slug' => 'robots-policy', 'robots' => 'index, follow, max-image-preview:large']);
        $this->app->instance('env', 'production');
        $this->get('https://rahbarhesab.com/blog/robots-policy/?utm_source=test')->assertOk()
            ->assertSee('content="noindex, follow, max-image-preview:large"', false);
        $post->update(['robots' => 'index, nofollow']);
        $this->get('https://rahbarhesab.com/blog/robots-policy/?utm_source=test')->assertOk()
            ->assertSee('content="noindex, nofollow"', false);
        // Laravel's test URL helper trims a terminal slash; exercise the exact URI through the kernel.
        $request = Request::create('https://rahbarhesab.com/blog/robots-policy/');
        $response = $this->app->make(Kernel::class)->handle($request);
        $this->createTestResponse($response, $request)->assertOk()
            ->assertSee('content="index, nofollow, max-snippet:-1, max-video-preview:-1, max-image-preview:large"', false);
    }

    public function test_nonproduction_always_blocks_indexing_and_missing_duration_is_not_invented(): void
    {
        $this->assertSame('noindex, nofollow', app(SeoService::class)->meta(['robots' => 'index, follow'])['robots']);
        $product = ShopProduct::factory()->course()->create();
        $schema = app(SeoService::class)->courseSchema($product);
        $this->assertArrayNotHasKey('courseWorkload', $schema['hasCourseInstance']);
    }

    public function test_supported_imported_robot_directives_are_preserved(): void
    {
        $value = 'INDEX,FOLLOW,max-snippet:-1,max-video-preview:-1,max-image-preview:large';
        $this->assertTrue(RobotsDirectives::valid($value));
        $this->assertSame('noindex, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large', RobotsDirectives::restrictive('noindex, follow', $value));
        $this->assertFalse(RobotsDirectives::valid('arbitrary-directive'));
        $this->assertSame('noindex, nofollow', RobotsDirectives::restrictive('index, follow', 'none'));
    }

    public function test_robots_never_duplicate_directives_when_content_has_none(): void
    {
        foreach ([null, '', 'index, follow,', ', '] as $content) {
            $this->assertSame('index, follow', RobotsDirectives::restrictive('index, follow', $content));
        }
        $this->assertSame('index, follow', RobotsDirectives::normalize('index, follow, '));
        $default = (string) config('seo.defaults.robots');
        $this->assertSame($default, RobotsDirectives::restrictive($default, null));
        $this->assertSame('noindex, follow', RobotsDirectives::restrictive($default, 'noindex, follow'));
    }

    public function test_excerpt_description_keeps_persian_characters_whole_and_skips_counters(): void
    {
        $seo = app(SeoService::class);
        $text = str_repeat('مالی ', 30).'تجاری بیشتر بیشتر';
        $description = $seo->excerptDescription('<p>'.$text.'</p>');

        $this->assertTrue(mb_check_encoding($description, 'UTF-8'));
        $this->assertStringEndsWith('تجاری', $description);
        $this->assertSame('پاسخگویی به سوالات تخصصی شما توسط کارشناسان', $seo->excerptDescription('<p>+100 نفر</p><p>پاسخگویی به سوالات تخصصی شما توسط کارشناسان</p>'));
        $this->assertSame('فقط عدد', $seo->excerptDescription('<p>فقط عدد</p>'));
    }

    public function test_wordpress_h1_texturize_matches_the_live_titles(): void
    {
        $this->assertSame('حق العملکاری – معاملات فصلی', wp_texturize('حق العملکاری - معاملات فصلی'));
        $this->assertSame('اخبار امروز “جدیدترین خبرها”', wp_texturize('اخبار امروز "جدیدترین خبرها"'));
    }

    public function test_titles_follow_rank_math_templates_of_the_live_site(): void
    {
        $seo = app(SeoService::class);
        $this->assertSame('مقالات Archives - راهبر حساب', $seo->wpTitle('مقالات Archives'));
        $this->assertSame('مقالات Archives - صفحه 2 از 7 - راهبر حساب', $seo->wpTitle('مقالات Archives', 2, 7));
        $this->assertSame('نوشته های راهبر حساب - صفحه 2 از 9 اخبار و مقالات حسابداری در راهبر حساب',
            $seo->wpTitle('نوشته های راهبر حساب', 2, 9, ' اخبار و مقالات حسابداری در راهبر حساب'));

        $this->app->instance('env', 'production');
        config(['seo.normalization_environments' => []]);
        $category = \App\Models\Category::factory()->post()->create(['slug' => 'news', 'name' => 'اخبار حسابداری']);
        $post = CmsPost::factory()->published()->create(['slug' => 'rank-math-title', 'title' => 'عنوان مقاله',
            'meta_title' => null, 'meta_description' => null,
            'body' => '<h2>تیتر</h2><p>'.str_repeat('متن پاراگراف اول ', 20).'</p><p>دوم</p>']);
        $post->categories()->attach($category->id, ['is_primary' => true]);
        $response = $this->get('/news/rank-math-title/')->assertOk()
            ->assertSee('<title>عنوان مقاله - راهبر حساب</title>', false)
            ->assertSee('max-image-preview:large', false)
            ->assertDontSee('follow, follow', false);
        preg_match('/<meta name="description" content="([^"]*)"/u', $response->getContent(), $match);
        $this->assertStringStartsWith('متن پاراگراف اول', $match[1]);
        $this->assertLessThanOrEqual(160, mb_strlen($match[1]));
        $this->get('/category/news/')->assertOk()->assertSee('<title>اخبار حسابداری Archives - راهبر حساب</title>', false);
        $this->get('/product/')->assertOk()->assertSee('<title>دوره های آموزش حسابداری - راهبر حساب</title>', false);
        $this->get('/blog/')->assertOk()->assertSee('<title>نوشته های راهبر حساب اخبار و مقالات حسابداری در راهبر حساب</title>', false);
    }

    public function test_noindex_category_from_rank_math_is_noindex_and_left_out_of_sitemap(): void
    {
        $this->app->instance('env', 'production');
        config(['seo.normalization_environments' => []]);
        $category = \App\Models\Category::factory()->product()->create(['slug' => 'accountants-choice',
            'name' => 'انتخاب حسابداران', 'robots' => 'noindex, nofollow, noarchive']);
        $product = ShopProduct::factory()->course()->create(['is_published' => true]);
        $product->categories()->attach($category->id, ['is_primary' => true]);
        $this->get('/product-category/accountants-choice/')->assertOk()
            ->assertSee('content="noindex, nofollow, noarchive"', false);
        $this->get('/product-sitemap.xml')->assertOk()->assertDontSee('accountants-choice', false);
    }
}
