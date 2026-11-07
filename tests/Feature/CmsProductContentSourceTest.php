<?php

namespace Tests\Feature;

use App\Models\CmsProduct;
use App\Services\SiteDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsProductContentSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_copy_wins_and_blank_fields_keep_the_code_default(): void
    {
        $service = app(SiteDataService::class);

        foreach (['rahbar', 'nojaro', 'vileo', 'wordpress-plugin'] as $slug) {
            $defaults = collect($service->defaultProducts())->firstWhere('slug', $slug);
            CmsProduct::query()->create([
                'slug' => $slug,
                'title' => 'عنوان '.$slug,
                'subtitle' => 'زیرعنوان '.$slug,
                'description' => '',
                'body' => null,
                'is_published' => true,
            ]);

            $product = $service->product($slug);

            $this->assertSame('عنوان '.$slug, $product['title']);
            $this->assertSame('زیرعنوان '.$slug, $product['subtitle']);
            $this->assertSame($defaults['description'], $product['description']);
            $this->assertSame($defaults['body'], $product['body']);
        }
    }
}
