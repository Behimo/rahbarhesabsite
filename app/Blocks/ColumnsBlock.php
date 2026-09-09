<?php

namespace App\Blocks;

use App\Services\BlockRegistry;

class ColumnsBlock extends AbstractBlock
{
    public const SECTIONS_PER_COLUMN = 4;

    public function type(): string
    {
        return 'columns';
    }

    public function label(): string
    {
        return 'چیدمان ستونی';
    }

    public function schema(): array
    {
        return [
            'columns' => [
                'type' => 'layout',
                'label' => 'ستون‌ها',
                'sections_per_column' => self::SECTIONS_PER_COLUMN,
            ],
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'columns' => [
                $this->emptyColumn(),
                $this->emptyColumn(),
            ],
        ];
    }

    public function render(array $settings): string
    {
        $registry = app(BlockRegistry::class);
        $columns = $this->normalizeColumns($settings['columns'] ?? []);

        $rendered = collect($columns)->map(function (array $column) use ($registry) {
            $sections = collect($column['sections'] ?? [])->map(function (array $section) use ($registry) {
                $html = '';

                foreach ($section['blocks'] ?? [] as $block) {
                    $type = $block['type'] ?? null;
                    if (! $type) {
                        continue;
                    }

                    try {
                        $html .= $registry->renderBlock($type, $block['settings'] ?? []);
                    } catch (\Throwable) {
                        continue;
                    }
                }

                return ['html' => $html];
            })->all();

            return ['sections' => $sections];
        })->all();

        return $this->view('blocks.columns', [
            'columns' => $rendered,
        ]);
    }

    /** @param  array<int, array<string, mixed>>  $columns */
    private function normalizeColumns(array $columns): array
    {
        if ($columns === []) {
            return [$this->emptyColumn()];
        }

        return collect($columns)->map(function (array $column) {
            if (isset($column['content']) && ! isset($column['sections'])) {
                $sections = array_fill(0, self::SECTIONS_PER_COLUMN, ['blocks' => []]);
                if (! empty($column['content'])) {
                    $sections[0]['blocks'][] = [
                        'type' => 'text',
                        'settings' => ['content' => $column['content']],
                    ];
                }

                return ['sections' => $sections];
            }

            $sections = $column['sections'] ?? [];

            while (count($sections) < self::SECTIONS_PER_COLUMN) {
                $sections[] = ['blocks' => []];
            }

            $sections = array_slice($sections, 0, self::SECTIONS_PER_COLUMN);

            foreach ($sections as &$section) {
                $section['blocks'] = $section['blocks'] ?? [];
            }

            return ['sections' => $sections];
        })->all();
    }

    private function emptyColumn(): array
    {
        return [
            'sections' => array_map(
                fn () => ['blocks' => []],
                range(1, self::SECTIONS_PER_COLUMN)
            ),
        ];
    }
}
