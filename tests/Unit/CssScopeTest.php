<?php

namespace Tests\Unit;

use App\Support\CssScope;
use PHPUnit\Framework\TestCase;

class CssScopeTest extends TestCase
{
    public function test_global_selectors_are_limited_to_the_page_container(): void
    {
        $css = <<<'CSS'
        a{color:#c36}
        body{margin:0}
        body.rtl .box, html[dir=rtl] .box{margin:1px}
        :root{--brand:red}
        *,:before{box-sizing:border-box}
        .foo:not(.a, .b){color:blue}
        @media (max-width: 600px) { h1{font-size:2rem} body{color:#111} }
        @font-face{font-family:X;src:url(a.woff)}
        @keyframes spin{from{opacity:0}to{opacity:1}}
        CSS;

        $scoped = (new CssScope)->scope($css, '.wp-imported-page');

        $this->assertStringContainsString('.wp-imported-page a{color:#c36}', $scoped);
        $this->assertStringContainsString('.wp-imported-page{margin:0}', $scoped);
        $this->assertStringContainsString('.wp-imported-page.rtl .box,.wp-imported-page[dir=rtl] .box{margin:1px}', $scoped);
        $this->assertStringContainsString('.wp-imported-page{--brand:red}', $scoped);
        $this->assertStringContainsString('.wp-imported-page *,.wp-imported-page :before{box-sizing:border-box}', $scoped);
        $this->assertStringContainsString('.wp-imported-page .foo:not(.a, .b){color:blue}', $scoped);
        $this->assertStringContainsString('@media (max-width: 600px) {.wp-imported-page h1{font-size:2rem}.wp-imported-page{color:#111}}', $scoped);
        $this->assertStringContainsString('@font-face{font-family:X;src:url(a.woff)}', $scoped);
        $this->assertStringContainsString('@keyframes spin{from{opacity:0}to{opacity:1}}', $scoped);
        $this->assertDoesNotMatchRegularExpression('/(?:^|[{}])\s*a\{/', $scoped);
        $this->assertDoesNotMatchRegularExpression('/(?:^|[{}])\s*body[\s.{]/', $scoped);
        $this->assertDoesNotMatchRegularExpression('/(?:^|[{}])\s*html[\s.{\[]/', $scoped);
    }
}
