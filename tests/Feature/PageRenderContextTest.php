<?php

namespace Tests\Feature;

use App\Blocks\AbstractBlock;
use App\Services\BlockRegistry;
use App\Services\BlockRenderer;
use App\Services\PageRenderContext;
use Tests\TestCase;

class PageRenderContextTest extends TestCase
{
    public function test_nested_rendering_restores_the_outer_page_context(): void
    {
        app(BlockRegistry::class)->register('nest-probe', NestingProbeBlock::class);
        $context = app(PageRenderContext::class);
        $context->set(['marker' => 'before']);

        app(BlockRenderer::class)->render([
            'blocks' => [
                ['type' => 'nest-probe', 'settings' => []],
            ],
        ], [
            'faqs' => [['q' => 'بیرونی', 'a' => 'پاسخ بیرونی', 'href' => '/blog']],
        ]);

        $this->assertSame('بیرونی', NestingProbeBlock::$seen);
        $this->assertSame('before', $context->get('marker'));
        $this->assertNull($context->get('faqs'));
    }
}

class NestingProbeBlock extends AbstractBlock
{
    public static ?string $seen = null;

    public function type(): string
    {
        return 'nest-probe';
    }

    public function label(): string
    {
        return 'آزمایش رندر تو در تو';
    }

    public function schema(): array
    {
        return [];
    }

    public function render(array $settings): string
    {
        app(BlockRenderer::class)->render([
            'blocks' => [
                ['type' => 'faq', 'settings' => []],
            ],
        ], [
            'faqs' => [['q' => 'داخلی', 'a' => 'پاسخ داخلی', 'href' => '/blog']],
        ]);

        $faqs = app(PageRenderContext::class)->get('faqs', []);
        self::$seen = $faqs[0]['q'] ?? null;

        return 'ok';
    }
}
