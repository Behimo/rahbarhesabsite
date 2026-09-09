<?php

namespace App\Services;

use App\Contracts\BlockInterface;
use App\Blocks\ColumnsBlock;
use App\Blocks\CoursesBlock;
use App\Blocks\CtaBlock;
use App\Blocks\FaqBlock;
use App\Blocks\HeroBlock;
use App\Blocks\HtmlBlock;
use App\Blocks\ImageBlock;
use App\Blocks\PostsBlock;
use App\Blocks\ProductsBlock;
use App\Blocks\StatsBlock;
use App\Blocks\TestimonialsBlock;
use App\Blocks\TextBlock;
use App\Blocks\VideoBlock;
use App\Support\Hook;
use InvalidArgumentException;

class BlockRegistry
{
    /** @var array<string, class-string<BlockInterface>> */
    private array $blocks = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    public function register(string $type, string $class): void
    {
        if (! is_subclass_of($class, BlockInterface::class)) {
            throw new InvalidArgumentException("Block must implement BlockInterface: {$class}");
        }

        $this->blocks[$type] = $class;
    }

    public function all(): array
    {
        return collect($this->blocks)->map(function (string $class) {
            $block = app($class);

            return [
                'type' => $block->type(),
                'label' => $block->label(),
                'schema' => $block->schema(),
                'defaults' => $block->defaultSettings(),
            ];
        })->values()->all();
    }

    public function make(string $type): BlockInterface
    {
        if (! isset($this->blocks[$type])) {
            throw new InvalidArgumentException("Unknown block type: {$type}");
        }

        return app($this->blocks[$type]);
    }

    public function renderBlock(string $type, array $settings): string
    {
        return $this->make($type)->render($settings);
    }

    public function collectAssets(array $blocks): array
    {
        $css = [];
        $js = [];

        foreach ($this->flattenBlocks($blocks) as $block) {
            $type = $block['type'] ?? null;
            if (! $type || ! isset($this->blocks[$type])) {
                continue;
            }

            $assets = $this->make($type)->assets();
            $css = array_merge($css, $assets['css'] ?? []);
            $js = array_merge($js, $assets['js'] ?? []);
        }

        return ['css' => array_unique($css), 'js' => array_unique($js)];
    }

    /** @param  array<int, array<string, mixed>>  $blocks */
    public function flattenBlocks(array $blocks): array
    {
        $flat = [];

        foreach ($blocks as $block) {
            $flat[] = $block;

            if (($block['type'] ?? '') !== 'columns') {
                continue;
            }

            foreach ($block['settings']['columns'] ?? [] as $column) {
                foreach ($column['sections'] ?? [] as $section) {
                    foreach ($section['blocks'] ?? [] as $nested) {
                        $flat = array_merge($flat, $this->flattenBlocks([$nested]));
                    }
                }
            }
        }

        return $flat;
    }

    private function registerDefaults(): void
    {
        $classes = Hook::applyFilters('cms.blocks.classes', [
            HeroBlock::class,
            TextBlock::class,
            HtmlBlock::class,
            ImageBlock::class,
            VideoBlock::class,
            CtaBlock::class,
            ColumnsBlock::class,
            FaqBlock::class,
            StatsBlock::class,
            TestimonialsBlock::class,
            PostsBlock::class,
            ProductsBlock::class,
            CoursesBlock::class,
            ...config('cms.blocks', []),
        ]);

        foreach (array_unique($classes) as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            $block = app($class);
            $this->register($block->type(), $class);
        }

        Hook::doAction('cms.blocks.boot', $this);
    }
}
