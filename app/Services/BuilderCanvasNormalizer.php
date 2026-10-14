<?php

namespace App\Services;

use App\Blocks\ColumnsBlock;

class BuilderCanvasNormalizer
{
    /**
     * @param  array<string, mixed>  $content
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    public function normalize(array $content, array $blocks): array
    {
        $defs = $this->definitions($blocks);
        $items = $content['blocks'] ?? [];

        if (! is_array($items) || $items === []) {
            $content['blocks'] = [];

            return $content;
        }

        $content['blocks'] = array_values(array_filter(array_map(function ($block) use ($defs) {
            if (! is_array($block)) {
                return null;
            }

            $type = (string) ($block['type'] ?? '');
            $settings = array_replace($this->defaultSettings($defs[$type] ?? null), $block['settings'] ?? []);

            if ($type === 'columns') {
                $settings = $this->normalizeColumns($settings);
            }

            return [
                'type' => $type,
                'settings' => $settings,
            ];
        }, array_values($items))));

        return $content;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, array<string, mixed>>
     */
    private function definitions(array $blocks): array
    {
        $defs = [];

        foreach ($blocks as $block) {
            $defs[$block['type']] = $block;
        }

        return $defs;
    }

    /**
     * @param  array<string, mixed>|null  $definition
     * @return array<string, mixed>
     */
    private function defaultSettings(?array $definition): array
    {
        if ($definition === null) {
            return [];
        }

        $defaults = $definition['defaults'] ?? [];

        if (is_array($defaults) && $defaults !== []) {
            return $defaults;
        }

        $settings = [];

        foreach ($definition['schema'] ?? [] as $key => $field) {
            $settings[$key] = $this->defaultForField($field);
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function defaultForField(array $field): mixed
    {
        if (array_key_exists('default', $field)) {
            return $field['default'];
        }

        return match ($field['type'] ?? '') {
            'repeater' => [],
            'number' => 0,
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function normalizeColumns(array $settings): array
    {
        $columns = $settings['columns'] ?? [];

        if (! is_array($columns) || $columns === []) {
            $settings['columns'] = [$this->emptyColumn(), $this->emptyColumn()];

            return $settings;
        }

        $normalized = [];

        foreach ($columns as $column) {
            if (! is_array($column)) {
                continue;
            }

            if (array_key_exists('content', $column) && ! array_key_exists('sections', $column)) {
                $sections = array_fill(0, ColumnsBlock::SECTIONS_PER_COLUMN, ['blocks' => []]);
                if (! empty($column['content'])) {
                    $sections[0]['blocks'][] = [
                        'type' => 'text',
                        'settings' => ['content' => $column['content']],
                    ];
                }
                $normalized[] = ['sections' => $sections];

                continue;
            }

            $sections = array_values($column['sections'] ?? []);

            while (count($sections) < ColumnsBlock::SECTIONS_PER_COLUMN) {
                $sections[] = ['blocks' => []];
            }

            $sections = array_slice($sections, 0, ColumnsBlock::SECTIONS_PER_COLUMN);

            foreach ($sections as &$section) {
                $section['blocks'] = $section['blocks'] ?? [];
            }
            unset($section);

            $normalized[] = ['sections' => $sections];
        }

        $settings['columns'] = $normalized !== [] ? $normalized : [$this->emptyColumn(), $this->emptyColumn()];

        return $settings;
    }

    /**
     * @return array{sections: array<int, array{blocks: array<int, mixed>}>}
     */
    private function emptyColumn(): array
    {
        return [
            'sections' => array_map(
                fn () => ['blocks' => []],
                range(1, ColumnsBlock::SECTIONS_PER_COLUMN)
            ),
        ];
    }
}
