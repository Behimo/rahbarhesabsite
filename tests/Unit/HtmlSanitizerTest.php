<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_clean_strips_scripts_styles_and_event_handlers(): void
    {
        $html = '<p style="color:red" onclick="alert(1)">Hi</p><script>alert(1)</script><style>.x{}</style><a href="javascript:alert(1)">x</a>';

        $clean = HtmlSanitizer::clean($html);

        $this->assertStringContainsString('<p>Hi</p>', $clean);
        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('style', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
    }

    public function test_clean_imported_keeps_styles_for_isolator_but_drops_dangerous_markup(): void
    {
        $html = '<div style="color:blue" onclick="evil()"><style>.x{}</style><p style="background:url(javascript:alert(1))">Ok</p><script>x</script></div>';

        $clean = HtmlSanitizer::cleanImported($html);

        $this->assertStringContainsString('style="color:blue"', $clean);
        $this->assertStringContainsString('<style>.x{}</style>', $clean);
        $this->assertStringContainsString('Ok', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:alert', $clean);
    }
}
