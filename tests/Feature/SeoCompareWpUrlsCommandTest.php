<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CmsPost;
use App\Models\CmsRedirect;
use App\Models\User;
use App\Services\Seo\UrlParityAuditor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class SeoCompareWpUrlsCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $output;

    protected function setUp(): void
    {
        parent::setUp();
        $this->output = storage_path('framework/testing/url-parity-'.uniqid().'.csv');
    }

    protected function tearDown(): void
    {
        if (is_file($this->output)) {
            unlink($this->output);
        }
        DB::disconnect('wordpress');
        DB::purge('wordpress');
        parent::tearDown();
    }

    public function test_kernel_matches_encoded_urls_deduplicates_sources_and_rolls_back_views(): void
    {
        $category = Category::query()->create(['type' => Category::TYPE_POST, 'name' => 'بلاگ', 'slug' => 'blogs']);
        $post = CmsPost::query()->create(['title' => 'عنوان', 'slug' => rawurlencode('تست'),
            'body' => '<p>متن</p>', 'status' => 'published', 'is_published' => true, 'views' => 7, 'published_at' => now()]);
        $post->categories()->attach($category->id, ['is_primary' => true]);
        $path = '/blogs/'.strtolower(rawurlencode('تست')).'/';
        Http::fake([
            'https://rahbarhesab.com/sitemap_index.xml' => Http::response($this->index(['post-sitemap.xml', 'video-sitemap.xml', 'local-sitemap.xml'])),
            'https://rahbarhesab.com/post-sitemap.xml' => Http::response($this->sitemap([$path, '/questions/missing/'], true)),
            'https://rahbarhesab.com/video-sitemap.xml' => Http::response($this->sitemap([$path])),
            'https://rahbarhesab.com/local-sitemap.xml' => Http::response($this->sitemap(['/locations.kml'])),
        ]);
        $views = $post->views;
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->artisan('seo:compare-wp-urls', ['--output' => $this->output])->assertFailed();
        $rows = collect($this->csv());
        $this->assertCount(3, $rows);
        $article = $rows->firstWhere('old_url', 'https://rahbarhesab.com/blogs/'.rawurlencode('تست').'/');
        $this->assertNotNull($article);
        $this->assertSame('ok', $article['result'], $article['notes']);
        $this->assertSame('post-sitemap.xml|video-sitemap.xml', $article['source']);
        $this->assertSame('missing', $rows->firstWhere('old_url', 'https://rahbarhesab.com/questions/missing/')['result']);
        $this->assertSame('kml', $rows->firstWhere('old_url', 'https://rahbarhesab.com/locations.kml')['result']);
        $this->assertSame($views, $post->fresh()->views);
        $this->assertSame('testing', app()->environment());
        $this->assertAuthenticatedAs($user);
    }

    public function test_limit_checks_only_first_url(): void
    {
        Http::fake(['*' => Http::response($this->sitemap(['/questions/', '/missing/']))]);
        $this->artisan('seo:compare-wp-urls', ['--limit' => 1, '--output' => $this->output])->assertSuccessful();
        $this->assertCount(1, $this->csv());
    }

    public function test_target_disables_redirect_following_and_requires_valid_one_hop_destination(): void
    {
        CmsRedirect::query()->create(['from_path' => '/old', 'to_path' => '/questions/', 'status_code' => 301, 'is_active' => true]);
        CmsRedirect::query()->create(['from_path' => '/broken', 'to_path' => '/next/', 'status_code' => 301, 'is_active' => true]);
        Http::fake([
            'https://rahbarhesab.com/sitemap_index.xml' => Http::response($this->sitemap(['/old/', '/broken/', '/unplanned/'])),
            'https://staging.example/old/' => function (Request $request, array $options) {
                $this->assertFalse($options['allow_redirects']);

                return Http::response('', 301, ['Location' => '/questions/']);
            },
            'https://staging.example/broken/' => Http::response('', 301, ['Location' => '/next/']),
            'https://staging.example/next/' => Http::response('', 301, ['Location' => '/questions/']),
            'https://staging.example/unplanned/' => Http::response('', 302, ['Location' => '/questions/']),
            'https://staging.example/questions/' => Http::response($this->html('/questions/', 'https://staging.example')),
        ]);
        $this->artisan('seo:compare-wp-urls', ['--target' => 'https://staging.example', '--output' => $this->output])->assertFailed();
        $rows = collect($this->csv());
        $valid = $rows->firstWhere('old_url', 'https://rahbarhesab.com/old/');
        $this->assertSame('ok-redirect', $valid['result'], $valid['notes']);
        $this->assertSame('https://staging.example/questions/', $valid['new_url']);
        $this->assertSame('redirected', $rows->firstWhere('old_url', 'https://rahbarhesab.com/broken/')['result']);
        $this->assertSame('redirected', $rows->firstWhere('old_url', 'https://rahbarhesab.com/unplanned/')['result']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://staging.example/old/');
    }

    public function test_intended_account_redirects_are_ok_and_wrong_destinations_fail(): void
    {
        Http::fake([
            'https://rahbarhesab.com/sitemap_index.xml' => Http::response($this->sitemap(['/my-account/', '/checkout/', '/'.rawurlencode('وضعیت-پرداخت').'/', '/order-tracking/'])),
            'https://staging.example/my-account/' => Http::response('', 301, ['Location' => 'https://staging.example/panel']),
            'https://staging.example/checkout/' => Http::response('', 302, ['Location' => '/login']),
            'https://staging.example/'.rawurlencode('وضعیت-پرداخت').'/' => Http::response('', 301, ['Location' => '/panel/orders']),
            'https://staging.example/order-tracking/' => Http::response('', 301, ['Location' => '/panel']),
        ]);
        $rows = collect(app(UrlParityAuditor::class)->audit(['target' => 'https://staging.example']));
        $this->assertSame('ok-redirect', $rows->firstWhere('old_url', 'https://rahbarhesab.com/my-account/')['result']);
        $this->assertSame('ok-redirect', $rows->firstWhere('old_url', 'https://rahbarhesab.com/checkout/')['result']);
        $this->assertSame('ok-redirect', $rows->firstWhere('old_url', 'https://rahbarhesab.com/'.rawurlencode('وضعیت-پرداخت').'/')['result']);
        $wrong = $rows->firstWhere('old_url', 'https://rahbarhesab.com/order-tracking/');
        $this->assertSame('redirected', $wrong['result']);
        $this->assertStringContainsString('Expected redirect to /panel/orders', $wrong['notes']);
    }

    public function test_redirect_origin_is_not_silently_replaced_when_validating_one_hop(): void
    {
        CmsRedirect::query()->create(['from_path' => '/old', 'to_path' => '/questions/', 'status_code' => 301, 'is_active' => true]);
        Http::fake([
            'https://rahbarhesab.com/sitemap_index.xml' => Http::response($this->sitemap(['/old/'])),
            'https://staging.example/old/' => Http::response('', 301, ['Location' => 'http://www.rahbarhesab.com/questions/']),
            'https://staging.example/questions/' => Http::response($this->html('/questions/')),
        ]);
        $rows = app(UrlParityAuditor::class)->audit(['target' => 'https://staging.example']);
        $this->assertSame('redirected', $rows[0]['result']);
        $this->assertStringContainsString('leaves target origin', $rows[0]['notes']);
        Http::assertNotSent(fn (Request $request) => $request->url() === 'http://www.rahbarhesab.com/questions/');
    }

    public function test_live_comparison_warns_for_metadata_and_links_but_does_not_hide_missing(): void
    {
        Http::fake([
            'https://rahbarhesab.com/sitemap_index.xml' => Http::response($this->sitemap(['/questions/', '/absent/'])),
            'https://staging.example/questions/' => Http::response($this->html('/questions/')),
            'https://staging.example/absent/' => Http::response('', 404),
            'https://rahbarhesab.com/questions/' => Http::response(str_replace('<h1>Title</h1>', '<h1>Other</h1><a href="/a/">A</a><a href="/b/">B</a>', $this->html('/questions/'))),
            'https://rahbarhesab.com/absent/' => Http::response($this->html('/absent/')),
        ]);
        $this->artisan('seo:compare-wp-urls', ['--target' => 'https://staging.example', '--compare-live' => true, '--output' => $this->output])->assertFailed();
        $rows = collect($this->csv());
        $this->assertSame('missing', $rows->firstWhere('old_url', 'https://rahbarhesab.com/absent/')['result']);
        $row = $rows->firstWhere('old_url', 'https://rahbarhesab.com/questions/');
        $this->assertSame('warn', $row['result']);
        $this->assertSame('Other', $row['wp_h1']);
        $this->assertStringContainsString('WP h1 differs', $row['notes']);
        $this->assertStringContainsString('>30%', $row['notes']);
    }

    public function test_connection_failures_are_error_rows_not_lost_batches(): void
    {
        Sleep::fake();
        Http::fake([
            'https://rahbarhesab.com/sitemap_index.xml' => Http::response($this->sitemap(['/questions/'])),
            'https://staging.example/*' => Http::failedConnection(),
        ]);
        $this->artisan('seo:compare-wp-urls', ['--target' => 'https://staging.example', '--output' => $this->output])->assertFailed();
        $this->assertSame('error', $this->csv()[0]['result']);
        Sleep::assertSleptTimes(2);
    }

    public function test_throttled_live_requests_are_retried(): void
    {
        Sleep::fake();
        Http::fake([
            'https://rahbarhesab.com/sitemap_index.xml' => Http::response($this->sitemap(['/questions/'])),
            'https://staging.example/questions/' => Http::response($this->html('/questions/')),
            'https://rahbarhesab.com/questions/' => Http::sequence()->push('', 429)->push($this->html('/questions/')),
        ]);
        $rows = app(UrlParityAuditor::class)->audit(['target' => 'https://staging.example', 'compare_live' => true]);
        $this->assertSame('Title', $rows[0]['wp_title']);
        Sleep::assertSleptTimes(1);
    }

    public function test_database_adds_populated_taxonomies_pagination_and_old_slugs(): void
    {
        config(['database.connections.wordpress' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'wp_']]);
        DB::purge('wordpress');
        $schema = Schema::connection('wordpress');
        $schema->create('options', function (Blueprint $table) {
            $table->string('option_name');
            $table->string('option_value');
        });
        $schema->create('posts', function (Blueprint $table) {
            $table->integer('ID');
            $table->string('post_type');
            $table->string('post_status');
            $table->string('post_name');
        });
        $schema->create('terms', function (Blueprint $table) {
            $table->integer('term_id');
            $table->string('slug');
        });
        $schema->create('term_taxonomy', function (Blueprint $table) {
            $table->integer('term_taxonomy_id');
            $table->integer('term_id');
            $table->integer('parent')->default(0);
            $table->string('taxonomy');
        });
        $schema->create('term_relationships', function (Blueprint $table) {
            $table->integer('object_id');
            $table->integer('term_taxonomy_id');
        });
        $schema->create('postmeta', function (Blueprint $table) {
            $table->id('meta_id');
            $table->integer('post_id');
            $table->string('meta_key');
            $table->string('meta_value');
        });
        $wp = DB::connection('wordpress');
        $wp->table('options')->insert(['option_name' => 'posts_per_page', 'option_value' => '1']);
        $wp->table('terms')->insert([['term_id' => 1, 'slug' => 'tax'], ['term_id' => 2, 'slug' => 'empty']]);
        $wp->table('term_taxonomy')->insert([['term_taxonomy_id' => 1, 'term_id' => 1, 'taxonomy' => 'category'], ['term_taxonomy_id' => 2, 'term_id' => 2, 'taxonomy' => 'category']]);
        foreach ([1, 2] as $id) {
            $wp->table('posts')->insert(['ID' => $id, 'post_type' => 'post', 'post_status' => 'publish', 'post_name' => 'post-'.$id]);
            $wp->table('term_relationships')->insert(['object_id' => $id, 'term_taxonomy_id' => 1]);
        }
        $wp->table('postmeta')->insert(['post_id' => 1, 'meta_key' => '_wp_old_slug', 'meta_value' => 'old']);
        Http::fake(function (Request $request) {
            return str_contains($request->url(), 'sitemap_index.xml')
                ? Http::response($this->sitemap(['/questions/']))
                : Http::response($this->html(parse_url($request->url(), PHP_URL_PATH)));
        });
        $rows = collect(app(UrlParityAuditor::class)->audit(['with_db' => true, 'target' => 'https://staging.example']));
        $this->assertNotNull($rows->firstWhere('old_url', 'https://rahbarhesab.com/category/tax/'));
        $this->assertNotNull($rows->firstWhere('old_url', 'https://rahbarhesab.com/category/tax/page/2/'));
        $this->assertNotNull($rows->firstWhere('old_url', 'https://rahbarhesab.com/blog/page/2/'));
        $this->assertNull($rows->firstWhere('old_url', 'https://rahbarhesab.com/category/empty/'));
        $old = $rows->firstWhere('old_url', 'https://rahbarhesab.com/tax/old/');
        $this->assertSame(301, $old['expected']);
        $this->assertSame('db:old-slug', $old['source']);
    }

    public function test_extraction_handles_persian_and_empty_alt_and_internal_links(): void
    {
        $data = app(UrlParityAuditor::class)->extractHtml('<html><head><title>عنوان فارسی</title></head><body><main><article><h1>سؤال</h1><h2>پاسخ</h2><img src="a"><img alt=" "><img alt="عنوان"><a href="/a/">A</a><a href="https://rahbarhesab.com/b/">B</a><a href="mailto:x@example.com">Mail</a><a href="https://external.example/">X</a></article></main></body></html>');
        $this->assertSame('عنوان فارسی', $data['title']);
        $this->assertSame('سؤال', $data['h1']);
        $this->assertSame(2, $data['images_missing_alt']);
        $this->assertSame(2, $data['internal_links']);
    }

    public function test_legacy_id_redirect_does_not_mark_homepage_as_expected_redirect(): void
    {
        CmsRedirect::query()->create(['from_path' => '/?p=100', 'to_path' => '/questions/', 'status_code' => 301, 'is_active' => true]);
        Http::fake([
            'https://rahbarhesab.com/sitemap_index.xml' => Http::response($this->sitemap(['/'])),
            'https://staging.example/' => Http::response($this->html('/')),
        ]);
        $rows = app(UrlParityAuditor::class)->audit(['target' => 'https://staging.example']);
        $this->assertSame(200, $rows[0]['expected']);
        $this->assertSame('ok', $rows[0]['result']);
    }

    public function test_progress_exception_restores_environment_request_and_transaction(): void
    {
        Http::fake(['*' => Http::response($this->sitemap(['/questions/']))]);
        $request = app('request');
        $level = DB::transactionLevel();
        try {
            app(UrlParityAuditor::class)->audit([], function () {
                CmsPost::query()->create(['title' => 'audit-test', 'slug' => 'audit-test']);
                throw new \RuntimeException('Interrupted audit');
            });
            $this->fail('Expected interruption.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Interrupted audit', $exception->getMessage());
        }
        $this->assertSame('testing', app()->environment());
        $this->assertSame($request, app('request'));
        $this->assertSame($level, DB::transactionLevel());
        $this->assertDatabaseMissing('cms_posts', ['slug' => 'audit-test']);
    }

    public function test_empty_sitemap_does_not_produce_a_false_success(): void
    {
        Http::fake(['*' => Http::response($this->sitemap([]))]);
        $this->artisan('seo:compare-wp-urls', ['--output' => $this->output])->assertFailed();
        $this->assertFileDoesNotExist($this->output);
    }

    public function test_local_sitemap_index_and_relative_child_are_supported(): void
    {
        $index = $this->output.'.xml';
        $child = $this->output.'.child.xml';
        if (! is_dir(dirname($index))) {
            mkdir(dirname($index), 0755, true);
        }
        file_put_contents($index, '<sitemapindex><sitemap><loc>'.basename($child).'</loc></sitemap></sitemapindex>');
        file_put_contents($child, $this->sitemap(['/questions/']));
        try {
            $this->artisan('seo:compare-wp-urls', ['--sitemap' => $index, '--output' => $this->output])->assertSuccessful();
            $this->assertCount(1, $this->csv());
        } finally {
            unlink($index);
            unlink($child);
        }
    }

    private function csv(): array
    {
        $file = fopen($this->output, 'rb');
        $this->assertSame("\xEF\xBB\xBF", fread($file, 3));
        $columns = fgetcsv($file, escape: '');
        $this->assertSame(UrlParityAuditor::COLUMNS, $columns);
        $rows = [];
        while (($row = fgetcsv($file, escape: '')) !== false) {
            $rows[] = array_combine($columns, $row);
        }
        fclose($file);

        return $rows;
    }

    private function index(array $children): string
    {
        return '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.implode('', array_map(fn ($child) => '<sitemap><loc>https://rahbarhesab.com/'.$child.'</loc></sitemap>', $children)).'</sitemapindex>';
    }

    private function sitemap(array $paths, bool $image = false): string
    {
        return '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'.implode('', array_map(fn ($path) => '<url><loc>https://rahbarhesab.com/'.$this->xmlPath($path).'</loc>'.($image ? '<image:image><image:loc>https://rahbarhesab.com/wp-content/image.jpg</image:loc></image:image>' : '').'</url>', $paths)).'</urlset>';
    }

    private function xmlPath(string $path): string
    {
        return htmlspecialchars(ltrim($path, '/'), ENT_XML1);
    }

    private function html(string $path, string $origin = 'https://rahbarhesab.com'): string
    {
        return '<html><head><title>Title</title><link rel="canonical" href="'.$origin.$path.'"><meta name="robots" content="index, follow"></head><body><main><h1>Title</h1></main></body></html>';
    }
}
