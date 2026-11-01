<?php

namespace Tests\Unit;

use App\Services\MigratedWpPageConverter;
use Tests\TestCase;

class MigratedWpPageConverterTest extends TestCase
{
    public function test_clean_html_strips_elementor_noise(): void
    {
        $converter = app(MigratedWpPageConverter::class);

        $html = <<<'HTML'
<!-- elementor -->
<div data-elementor-type="wp-page" data-elementor-id="1" class="elementor elementor-1" data-settings="{&quot;a&quot;:1}">
<style>.x{color:red}</style>
<link rel="stylesheet" href="/x.css">
<p class="elementor-heading-title">Hello</p>
</div>
HTML;

        $clean = $converter->cleanHtml($html);

        $this->assertStringNotContainsString('<!--', $clean);
        $this->assertStringNotContainsString('data-elementor', $clean);
        $this->assertStringNotContainsString('data-settings', $clean);
        $this->assertStringNotContainsString('<style', $clean);
        $this->assertStringNotContainsString('<link', $clean);
        $this->assertStringContainsString('class="elementor elementor-1"', $clean);
        $this->assertStringContainsString('Hello', $clean);
    }

    public function test_key_for_persian_slug(): void
    {
        $converter = app(MigratedWpPageConverter::class);

        $this->assertSame('privacy-policy', $converter->keyForSlug('سیاست-حفظ-حریم-خصوصی'));
        $this->assertSame('terms', $converter->keyForSlug('terms'));
    }
}
