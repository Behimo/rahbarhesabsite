<?php

namespace Tests\Unit;

use App\Services\Comments\CommentSanitizer;
use PHPUnit\Framework\TestCase;

class CommentSanitizerTest extends TestCase
{
    private CommentSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new CommentSanitizer;
    }

    public function test_strips_script_tags(): void
    {
        $clean = $this->sanitizer->sanitize('<script>alert(1)</script>سلام');

        $this->assertSame('سلام', $clean);
        $this->assertStringNotContainsString('<script>', $clean);
    }

    public function test_strips_encoded_script_tags(): void
    {
        $clean = $this->sanitizer->sanitize('&lt;script&gt;alert(1)&lt;/script&gt;متن');

        $this->assertSame('متن', $clean);
    }

    public function test_strips_img_onerror(): void
    {
        $clean = $this->sanitizer->sanitize('<img src=x onerror=alert(1)>ok');

        $this->assertSame('ok', $clean);
    }

    public function test_strips_bidi_overrides(): void
    {
        $clean = $this->sanitizer->sanitize("سلام\u{202E}evil");

        $this->assertSame('سلامevil', $clean);
    }

    public function test_preserves_newlines_but_collapses_excess(): void
    {
        $clean = $this->sanitizer->sanitize("خط یک\n\n\n\nخط دو");

        $this->assertSame("خط یک\n\nخط دو", $clean);
    }
}
