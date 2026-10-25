<?php

namespace Tests\Feature;

use App\Models\CmsRedirect;
use App\Models\ShopProduct;
use App\Services\PermalinkService;
use App\Services\RankMathRedirectImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUrlParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_named_product_routes_emit_final_wp_urls_with_trailing_slash(): void
    {
        config(['seo.trailing_slash' => true]);

        $product = ShopProduct::factory()->course()->create([
            'slug' => 'parity-url-course',
            'is_published' => true,
        ]);

        $permalinks = app(PermalinkService::class);
        $expected = $permalinks->productAbsolute($product);

        $this->assertSame($expected, route('courses.show', $product->slug));
        $this->assertSame($permalinks->absolute('/product/'), route('courses.index'));
        $this->assertSame($expected, $product->url());
        $this->assertStringEndsWith('/', route('courses.show', $product->slug));
    }

    public function test_product_detail_responds_ok_without_following_a_redirect(): void
    {
        $product = ShopProduct::factory()->course()->create([
            'slug' => 'direct-product-hit',
            'is_published' => true,
        ]);

        // Tests skip NormalizeSeoUrl trailing-slash middleware; hit the final path.
        $this->get('/product/direct-product-hit/')
            ->assertOk()
            ->assertSee($product->title, false);
    }

    public function test_legacy_plural_products_path_is_not_used_by_named_routes(): void
    {
        $this->assertStringNotContainsString('/products/', route('courses.show', 'any-slug'));
        $this->assertStringNotContainsString('/courses/', route('courses.show', 'any-slug'));
    }

    public function test_product_urls_do_not_redirect(): void
    {
        ShopProduct::factory()->course()->create([
            'slug' => 'no-redirect-course',
            'is_published' => true,
        ]);

        $this->get('/product/no-redirect-course/')
            ->assertOk()
            ->assertHeaderMissing('Location');

        $this->get('/product/no-redirect-course')
            ->assertOk()
            ->assertHeaderMissing('Location');

        $this->get('/products/no-redirect-course')->assertNotFound();
        $this->get('/courses/no-redirect-course')->assertNotFound();
    }

    public function test_rankmath_import_skips_inverted_product_to_courses_redirects(): void
    {
        $result = app(RankMathRedirectImporter::class)->import(
            "/product/sample-course,/courses/sample-course,301\n".
            "/old-path,/product/sample-course/,301\n"
        );

        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(0, CmsRedirect::query()->where('from_path', 'like', '/product/%')->where('to_path', 'like', '/courses/%')->count());
        $this->assertDatabaseHas('cms_redirects', [
            'from_path' => '/old-path',
            'to_path' => '/product/sample-course/',
        ]);
    }
}
