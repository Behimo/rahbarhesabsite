<?php

namespace Tests\Unit;

use App\Services\PermalinkService;
use App\Support\Money;
use App\Support\PersianSlug;
use App\Support\PhoneNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LaunchContractsTest extends TestCase
{
    #[DataProvider('moneyAmounts')]
    public function test_gateway_amounts_are_exactly_ten_times_the_store_amount(int $toman, int $rials): void
    {
        $this->assertSame('IRT', Money::STORE_CURRENCY);
        $this->assertSame($rials, Money::tomanToRials($toman));
    }

    public static function moneyAmounts(): array
    {
        return [
            'free' => [0, 0],
            'one toman' => [1, 10],
            'large purchase' => [125000000, 1250000000],
        ];
    }

    #[DataProvider('mobileNumbers')]
    public function test_mobile_formats_have_the_same_identity(string $input): void
    {
        $this->assertTrue(PhoneNormalizer::isValidIranMobile($input));
        $this->assertSame('09123456789', PhoneNormalizer::toLocal($input));
        $this->assertSame('+989123456789', PhoneNormalizer::toE164($input));
    }

    public static function mobileNumbers(): array
    {
        return [
            'local' => ['09123456789'],
            'international' => ['+989123456789'],
            'without prefix' => ['9123456789'],
            'persian digits' => ['۰۹۱۲۳۴۵۶۷۸۹'],
            'arabic digits' => ['٠٩١٢٣٤٥٦٧٨٩'],
        ];
    }

    #[DataProvider('invalidMobiles')]
    public function test_invalid_mobile_numbers_are_rejected(string $input): void
    {
        $this->assertFalse(PhoneNormalizer::isValidIranMobile($input));
    }

    public static function invalidMobiles(): array
    {
        return [
            'empty' => [''],
            'landline' => ['02112345678'],
            'short' => ['0912345678'],
            'long' => ['091234567890'],
            'foreign' => ['+447911123456'],
        ];
    }

    public function test_persian_otp_digits_are_normalized_before_verification(): void
    {
        $this->assertSame('012345', PhoneNormalizer::normalizeOtpCode('۰۱۲۳۴۵'));
        $this->assertSame('012345', PhoneNormalizer::normalizeOtpCode('٠١٢٣٤٥'));
        $this->assertNull(PhoneNormalizer::normalizeOtpCode(null));
    }

    public function test_persian_slug_resolves_collisions_without_replacing_existing_slugs(): void
    {
        $existing = ['rahnmay-malyaty', 'rahnmay-malyaty-2'];
        $slug = PersianSlug::make('راهنمای مالیاتی');

        $this->assertSame('rahnmay-malyaty', $slug);
        $this->assertSame('rahnmay-malyaty-3', PersianSlug::unique(
            'راهنمای مالیاتی',
            static fn (string $candidate): bool => in_array($candidate, $existing, true)
        ));
    }

    public function test_permalink_normalization_preserves_files_and_applies_trailing_slashes_to_pages(): void
    {
        config(['seo.trailing_slash' => true]);
        $permalinks = app(PermalinkService::class);

        $this->assertSame('/', $permalinks->normalizePath('/'));
        $this->assertSame('/product/course/', $permalinks->normalizePath('product/course'));
        $this->assertSame('/sitemap_index.xml', $permalinks->normalizePath('/sitemap_index.xml'));
        $this->assertSame('/robots.txt', $permalinks->normalizePath('/robots.txt/'));

        config(['seo.trailing_slash' => false]);
        $this->assertSame('/product/course', $permalinks->normalizePath('/product/course'));
    }

    public function test_reserved_path_constraint_only_excludes_complete_segments(): void
    {
        config(['seo.reserved_prefixes' => ['product', 'blog']]);
        $permalinks = app(PermalinkService::class);
        $pattern = '~'.$permalinks->reservedSegmentPattern().'~';

        $this->assertSame(0, preg_match($pattern, 'product'));
        $this->assertSame(0, preg_match($pattern, 'blog'));
        $this->assertSame(1, preg_match($pattern, 'productivity'));
        $this->assertSame(1, preg_match($pattern, 'builder-page'));
        $this->assertSame(0, preg_match($pattern, 'anything/nested'));

        $this->assertSame(1, preg_match('~'.$permalinks->reservedSegmentPattern(['blog']).'~', 'blog'));
    }
}
