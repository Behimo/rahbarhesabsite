<?php

namespace Tests\Unit;

use App\Services\ContentUrlRewriter;
use Tests\TestCase;

class ContentUrlRewriterTest extends TestCase
{
    public function test_it_hydrates_perfmatters_lazy_images_to_real_src(): void
    {
        $html = '<img class="wp-image-1 perfmatters-lazy" src="data:image/svg+xml,%3Csvg/%3E" '
            .'data-src="https://rahbarhesab.com/wp-content/uploads/2026/09/photo.webp" '
            .'data-srcset="https://rahbarhesab.com/wp-content/uploads/2026/09/photo.webp 900w" '
            .'data-sizes="(max-width: 900px) 100vw, 900px" alt="x">';

        $out = app(ContentUrlRewriter::class)->rewrite($html);

        $this->assertStringContainsString('src="/wp-content/uploads/2026/09/photo.webp"', $out);
        $this->assertStringContainsString('srcset="/wp-content/uploads/2026/09/photo.webp 900w"', $out);
        $this->assertStringContainsString('sizes="(max-width: 900px) 100vw, 900px"', $out);
        $this->assertStringNotContainsString('data-src=', $out);
        $this->assertStringNotContainsString('perfmatters-lazy', $out);
        $this->assertStringNotContainsString('data:image/svg', $out);
    }

    public function test_it_applies_data_bg_as_background_image(): void
    {
        $html = '<div class="elementor-custom-embed-image-overlay perfmatters-lazy" style '
            .'data-bg="http://localhost:8000/wp-content/uploads/2026/10/poster.webp"></div>';

        $out = app(ContentUrlRewriter::class)->rewrite($html);

        $this->assertStringContainsString('background-image:url(/wp-content/uploads/2026/10/poster.webp)', $out);
        $this->assertStringNotContainsString('data-bg=', $out);
        $this->assertStringNotContainsString('perfmatters-lazy', $out);
        $this->assertSame(1, preg_match_all('/\bstyle\s*=/', $out));
    }

    public function test_it_hydrates_lazy_video_src_and_poster(): void
    {
        $html = '<video class="elementor-video perfmatters-lazy" controls '
            .'data-src="https://www.dl.rahbarhesab.com/video.mp4" '
            .'data-poster="http://localhost:8000/wp-content/uploads/2026/10/poster.webp"></video>';

        $out = app(ContentUrlRewriter::class)->rewrite($html);

        $this->assertStringContainsString('src="https://www.dl.rahbarhesab.com/video.mp4"', $out);
        $this->assertStringContainsString('poster="/wp-content/uploads/2026/10/poster.webp"', $out);
        $this->assertStringNotContainsString('data-src=', $out);
        $this->assertStringNotContainsString('data-poster=', $out);
    }
}
