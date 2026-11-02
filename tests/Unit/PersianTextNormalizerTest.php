<?php

namespace Tests\Unit;

use App\Support\PersianTextNormalizer;
use PHPUnit\Framework\TestCase;

class PersianTextNormalizerTest extends TestCase
{
    public function test_normalizes_arabic_ye_and_kaf(): void
    {
        $this->assertSame('کتاب یار', PersianTextNormalizer::normalize('كتاب يار'));
    }

    public function test_detects_word_with_separators(): void
    {
        $this->assertTrue(PersianTextNormalizer::containsWord('این ک.ل.م.ه بد است', 'کلمه'));
    }
}
