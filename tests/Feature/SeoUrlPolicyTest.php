<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleCmsRedirects;
use App\Http\Middleware\NormalizeSeoUrl;
use App\Models\CmsPost;
use App\Models\CmsRedirect;
use App\Services\PermalinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SeoUrlPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function production(): void
    {
        $this->app->instance('env', 'production');
        config(['seo.canonical_host' => 'rahbarhesab.com', 'seo.force_https' => true, 'seo.force_www' => false]);
    }

    public function test_normalization_combines_host_https_and_slash_and_preserves_query(): void
    {
        $this->production();
        $this->get('http://www.rahbarhesab.com/blog?name=%D8%AA%D8%B3%D8%AA&utm_source=test')
            ->assertStatus(301)
            ->assertRedirect('https://rahbarhesab.com/blog/?name=%D8%AA%D8%B3%D8%AA&utm_source=test');
        $request = Request::create('http://www.rahbarhesab.com/blog', 'HEAD');
        $response = app(NormalizeSeoUrl::class)->handle($request, fn () => response('', 204));
        $this->assertSame('https://rahbarhesab.com/blog/', $response->headers->get('Location'));
    }

    public function test_missing_trailing_slash_redirects_once_to_slash_url(): void
    {
        $this->production();
        $this->get('https://rahbarhesab.com/blog')
            ->assertStatus(301)
            ->assertRedirect('https://rahbarhesab.com/blog/');
    }

    public function test_slash_url_is_served_directly_without_redirect(): void
    {
        $this->production();
        $request = Request::create('https://rahbarhesab.com/blog/');
        $next = response('untouched', 200);
        $response = app(NormalizeSeoUrl::class)->handle($request, fn () => $next);
        $this->assertSame($next, $response);
        $this->assertFalse($response->headers->has('Location'));
    }

    public function test_paginated_and_nested_paths_get_trailing_slash(): void
    {
        $this->production();
        foreach (['/blog/page/2', '/category/x/page/3'] as $path) {
            $this->get('https://rahbarhesab.com'.$path)
                ->assertStatus(301)
                ->assertRedirect('https://rahbarhesab.com'.$path.'/');
        }
    }

    public function test_normalization_environments_are_configurable(): void
    {
        $this->app->instance('env', 'staging');
        config(['seo.normalization_environments' => ['production', 'staging']]);
        $request = Request::create('https://delicollection.ir/blog');
        $this->app->instance('request', $request);
        // HTTP requests on staging use the incoming origin, not the CLI APP_URL.
        $consoleMode = new \ReflectionProperty($this->app, 'isRunningInConsole');
        $wasConsole = $consoleMode->getValue($this->app);
        $consoleMode->setValue($this->app, false);
        try {
            $response = app(NormalizeSeoUrl::class)->handle($request, fn () => response('', 204));
            $this->assertSame(301, $response->getStatusCode());
            $this->assertSame('https://delicollection.ir/blog/', $response->headers->get('Location'));
        } finally {
            $consoleMode->setValue($this->app, $wasConsole);
        }
    }

    public function test_final_live_product_url_is_not_redirected(): void
    {
        $this->production();
        $request = Request::create('https://rahbarhesab.com/product/final-course/');
        $response = app(NormalizeSeoUrl::class)->handle($request, fn () => response('final', 200));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_unsafe_methods_and_infrastructure_and_files_bypass_both_middlewares(): void
    {
        $this->production();
        foreach (['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
            foreach (['/blog', '/admin/posts', '/api/v1/posts', '/panel', '/build/site.css'] as $path) {
                $request = Request::create('http://www.rahbarhesab.com'.$path, $method);
                foreach ([NormalizeSeoUrl::class, HandleCmsRedirects::class] as $middleware) {
                    $response = app($middleware)->handle($request, fn () => response('untouched', 204));
                    $this->assertSame(204, $response->getStatusCode(), $method.' '.$path);
                }
            }
        }
        foreach (['/admin', '/api/v1/posts', '/panel', '/courses/test/learn', '/sitemap.xml'] as $path) {
            $request = Request::create('http://www.rahbarhesab.com'.$path);
            $this->assertSame(204, app(NormalizeSeoUrl::class)->handle($request, fn () => response('', 204))->getStatusCode());
        }
    }

    public function test_local_requests_do_not_force_production_origin(): void
    {
        $this->get('/blog')->assertOk()->assertHeaderMissing('Location');
    }

    public function test_chain_resolves_directly_to_normalized_destination(): void
    {
        CmsRedirect::create(['from_path' => '/old', 'to_path' => '/middle', 'status_code' => 301, 'is_active' => true]);
        CmsRedirect::create(['from_path' => '/middle', 'to_path' => '/blog', 'status_code' => 301, 'is_active' => true]);
        $this->production();
        $this->get('http://www.rahbarhesab.com/old')->assertRedirect('https://rahbarhesab.com/blog/');
    }

    public function test_loops_gone_query_precedence_and_cache_invalidation(): void
    {
        $row = CmsRedirect::create(['from_path' => '/old', 'to_path' => '/old/', 'status_code' => 301, 'is_active' => true]);
        $this->get('/old')->assertNotFound();
        $row->update(['to_path' => '/blog']);
        $this->get('/old')->assertRedirect(app(PermalinkService::class)->absolute('/blog'));
        $row->update(['status_code' => 410]);
        $this->get('/old')->assertStatus(410)->assertHeaderMissing('Location');
        $row->delete();
        $this->get('/old')->assertNotFound();

        CmsRedirect::create(['from_path' => '/old', 'to_path' => '/blog', 'status_code' => 301, 'is_active' => true]);
        CmsRedirect::create(['from_path' => '/old', 'from_query' => 'x=1', 'match_query' => true, 'to_path' => '/search', 'status_code' => 302, 'is_active' => true]);
        $this->get('/old?x=1&extra=2')->assertStatus(302)->assertRedirect(app(PermalinkService::class)->absolute('/search'));
        $this->get('/old?x=2')->assertRedirect(app(PermalinkService::class)->absolute('/blog'));
    }

    public function test_legacy_wordpress_id_resolves_published_content_only(): void
    {
        $post = CmsPost::factory()->published()->create(['wp_post_id' => 5001]);
        $this->get('/?p=5001')->assertRedirect(app(PermalinkService::class)->postAbsolute($post));
        $this->get('/?p[]=5001')->assertOk();
    }

    public function test_two_node_loops_and_unsafe_targets_fall_through(): void
    {
        CmsRedirect::create(['from_path' => '/a', 'to_path' => '/b', 'status_code' => 301, 'is_active' => true]);
        CmsRedirect::create(['from_path' => '/b', 'to_path' => '/a', 'status_code' => 301, 'is_active' => true]);
        $this->get('/a')->assertNotFound();
        foreach (['//evil.test', 'javascript:alert(1)', "/blog\r\nX-Test: bad", '/\\evil.test'] as $i => $target) {
            CmsRedirect::create(['from_path' => '/unsafe-'.$i, 'to_path' => $target, 'status_code' => 301, 'is_active' => true]);
            $this->get('/unsafe-'.$i)->assertNotFound();
        }
    }

    public function test_absolute_www_history_target_is_collapsed_to_canonical_in_one_hop(): void
    {
        CmsRedirect::create(['from_path' => '/old-www', 'to_path' => 'http://www.rahbarhesab.com/middle', 'status_code' => 301, 'is_active' => true]);
        CmsRedirect::create(['from_path' => '/middle', 'to_path' => '/blog', 'status_code' => 301, 'is_active' => true]);
        $this->production();
        $this->get('https://rahbarhesab.com/old-www')->assertRedirect('https://rahbarhesab.com/blog/');
    }

    public function test_normalization_preserves_encoded_persian_path_and_respects_origin_flags(): void
    {
        $this->production();
        $slug = rawurlencode('آموزش-مالیات');
        $request = Request::create('http://www.rahbarhesab.com/blog/'.$slug.'?ref=test');
        $response = app(NormalizeSeoUrl::class)->handle($request, fn () => response('', 204));
        $this->assertSame('https://rahbarhesab.com/blog/'.$slug.'/?ref=test', $response->headers->get('Location'));
        config(['seo.force_www' => true, 'seo.force_https' => false, 'app.url' => 'http://rahbarhesab.com']);
        $this->assertSame('http://www.rahbarhesab.com/blog/', app(PermalinkService::class)->absolute('/blog'));
    }

    public function test_trusted_proxy_https_is_not_redirected_back_to_itself(): void
    {
        $this->production();
        $request = Request::create('http://rahbarhesab.com/blog/', server: [
            'REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);
        Request::setTrustedProxies(['10.0.0.1'], Request::HEADER_X_FORWARDED_PROTO);
        try {
            $response = app(NormalizeSeoUrl::class)->handle($request, fn () => response('', 204));
            $this->assertSame(204, $response->getStatusCode());
        } finally {
            Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_PROTO);
        }
    }

    public function test_path_normalization_preserves_query_and_fragments_and_setting(): void
    {
        $permalinks = app(PermalinkService::class);
        $this->assertSame('/blog/?q=tax#heading', $permalinks->normalizePath('/blog?q=tax#heading'));
        $this->assertSame('/admin/posts', $permalinks->normalizePath('/admin/posts/'));
        config(['seo.trailing_slash' => false]);
        $this->assertSame('/blog', $permalinks->normalizePath('/blog/'));
    }
}
