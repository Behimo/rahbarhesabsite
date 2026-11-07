<?php

namespace Tests\Feature;

use App\Models\CmsProduct;
use App\Services\Cms\CmsProductRepository;
use App\Services\Cms\DefaultProductContent;
use App\Services\SiteDataService;
use App\Services\Wordpress\WordpressIdentity;
use App\Services\Wordpress\WordpressImporter;
use App\Services\Wordpress\WordpressProductValues;
use App\Services\Wordpress\WordpressRenderedPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArchitectureExtractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_repository_keeps_published_order_and_presented_cache_contract(): void
    {
        Cache::forget('cms_products');
        CmsProduct::query()->create(['slug' => 'hidden', 'title' => 'Hidden', 'is_published' => false]);
        $later = CmsProduct::query()->create(['slug' => 'later', 'title' => 'Later', 'sort_order' => 20, 'is_published' => true]);
        CmsProduct::query()->create(['slug' => 'first', 'title' => 'First', 'sort_order' => 10, 'is_published' => true]);

        $repository = app(CmsProductRepository::class);
        $service = app(SiteDataService::class);
        $this->assertSame(['first', 'later'], $repository->published()->pluck('slug')->all());
        $this->assertNull($repository->findPublished('hidden'));
        $this->assertNull($service->product('hidden'));
        $this->assertSame(['first', 'later'], $service->products()->pluck('slug')->all());
        $this->assertIsArray(Cache::get('cms_products')->first());

        $later->update(['title' => 'Changed']);
        $this->assertSame('Later', $service->products()->last()['title']);
        $this->assertSame('Changed', $service->product('later')['title']);
        Cache::forget('cms_products');
        $this->assertSame('Changed', $service->products()->last()['title']);
    }

    public function test_defaults_remain_fallback_copy_not_synthetic_published_products(): void
    {
        Cache::forget('cms_products');
        $service = app(SiteDataService::class);
        $this->assertSame(app(DefaultProductContent::class)->products(), $service->defaultProducts());
        $this->assertSame(['rahbar', 'nojaro', 'vileo', 'wordpress-plugin'], array_column($service->defaultProducts(), 'slug'));
        $this->assertTrue($service->products()->isEmpty());
        $this->assertNull($service->product('rahbar'));
    }

    public function test_product_values_preserve_sale_and_duration_rules(): void
    {
        $values = new WordpressProductValues;
        $this->assertSame([120, 80], $values->regularAndSalePrice(['_regular_price' => '120', '_price' => '80', '_sale_price' => '80']));
        $this->assertSame([100, 70], $values->regularAndSalePrice(['_regular_price' => '0', '_price' => '100', '_sale_price' => '70']));
        $this->assertSame([100, null], $values->regularAndSalePrice(['_regular_price' => '100', '_sale_price' => '']));
        $this->assertSame(155, $values->minutesFromDurationLabel('۲ ساعت و ۳۵ دقیقه'));
        $this->assertSame(90, $values->minutesFromDurationLabel('١ ساعت و ٣٠'));
        $this->assertNull($values->minutesFromDurationLabel('بدون زمان'));
        $this->assertSame('first', $values->normalizeSpotplayerCourseId(' , first, second'));
    }

    public function test_identity_resolution_preserves_email_ownership_and_mobile_tiebreaks(): void
    {
        $identity = new WordpressIdentity;
        $emails = [];
        $this->assertSame('Person@example.test', $identity->claimUniqueEmail(' Person@example.test ', 7, $emails));
        $this->assertNull($identity->claimUniqueEmail('person@example.test', 8, $emails));
        $this->assertSame('person@example.test', $identity->claimUniqueEmail('person@example.test', 7, $emails));
        $this->assertSame('09123456789', $identity->resolveMobile(['billing_phone' => '+989123456789'], 'invalid'));
        $candidates = array_map(fn ($id, $orders) => ['row' => (object) ['ID' => $id], 'mobile' => '09123456789', 'orders' => $orders], [5, 3, 1], [4, 4, 2]);
        $this->assertSame(['09123456789' => 3], $identity->mobileWinners($candidates));
    }

    public function test_rendered_page_composition_preserves_protected_override_hooks(): void
    {
        Http::fake(['https://source.test/*' => Http::response('<main>Original</main>')]);
        $importer = new class extends WordpressImporter
        {
            public function fetch(): ?array
            {
                return $this->fetchRenderedWpPage('page', 9);
            }

            protected function wpSiteUrl(): string
            {
                return 'https://source.test';
            }

            protected function extractElementorRoot(string $html): ?string
            {
                return 'Overridden root';
            }

            protected function collectElementorStylesheets(string $html, int $wpId, string $siteUrl): array
            {
                return [$siteUrl.'/override.css'];
            }
        };
        $this->assertSame(['html' => 'Overridden root', 'stylesheets' => ['https://source.test/override.css']], $importer->fetch());
        Http::assertSentCount(1);
    }

    public function test_rendered_page_parser_and_css_rewriter_preserve_existing_rules(): void
    {
        $rendered = new WordpressRenderedPage;
        $root = '<div data-elementor-type="wp-page"><div>Nested</div></div>';
        $this->assertSame($root, $rendered->extractElementorRoot('<header>Ignore</header>'.$root.'<footer>Ignore</footer>'));
        $this->assertSame('Classic', $rendered->extractElementorRoot('<main> Classic </main>'));
        $this->assertNull($rendered->extractElementorRoot('<div>No content region</div>'));
        $this->assertSame('a{background:url(https://source.test/css/../image.png)}', $rendered->rewriteCssAssetUrls('a{background:url(../image.png)}', 'https://source.test/css/page.css'));
        $this->assertSame(['https://source.test/elementor.css', 'https://source.test/wp-content/uploads/elementor/css/post-9.css'], $rendered->collectElementorStylesheets('<link rel="stylesheet" href="/elementor.css"><link rel="stylesheet" href="/unrelated.css">', 9, 'https://source.test'));
    }
}
