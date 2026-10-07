<?php

namespace App\Services;

use App\Blocks\ColumnsBlock;

class BuilderCanvasRenderer
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
     * @param  array<string, mixed>  $content
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public function render(array $content, array $blocks): string
    {
        $defs = $this->definitions($blocks);
        $items = $content['blocks'] ?? [];

        if (! is_array($items) || $items === []) {
            return '<div class="builder-empty">از سمت راست یک بلوک اضافه کنید.</div>';
        }

        $html = '';

        foreach (array_values($items) as $index => $block) {
            $type = (string) ($block['type'] ?? '');
            $label = e($defs[$type]['label'] ?? $type);
            $body = $type === 'columns'
                ? $this->renderColumns($index, $block, $defs)
                : $this->renderSchemaFields($index, $defs[$type]['schema'] ?? [], $block['settings'] ?? [], '');

            $html .= <<<HTML
            <div class="builder-block" data-block-index="{$index}">
                <div class="builder-block-header" data-toggle-block="{$index}">
                    <i class="ti ti-grip-vertical text-muted"></i>
                    <strong class="flex-grow-1">{$label}</strong>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" data-move-up="{$index}" title="بالا"><i class="ti ti-arrow-up"></i></button>
                        <button type="button" class="btn btn-outline-secondary" data-move-down="{$index}" title="پایین"><i class="ti ti-arrow-down"></i></button>
                        <button type="button" class="btn btn-outline-danger" data-remove="{$index}" title="حذف"><i class="ti ti-trash"></i></button>
                    </div>
                    <i class="ti ti-chevron-down"></i>
                </div>
                <div class="builder-block-body">{$body}</div>
            </div>
            HTML;
        }

        return $html;
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

    /**
     * @param  array<string, array<string, mixed>>  $schema
     * @param  array<string, mixed>  $settings
     */
    private function renderSchemaFields(int $blockIndex, array $schema, array $settings, string $pathPrefix): string
    {
        $html = '';

        foreach ($schema as $key => $field) {
            $path = $pathPrefix === '' ? (string) $key : $pathPrefix.'.'.$key;
            $value = array_key_exists($key, $settings) ? $settings[$key] : $this->defaultForField($field);
            $html .= $this->renderField($blockIndex, $field, $value, $path);
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function renderField(int $blockIndex, array $field, mixed $value, string $dataPath): string
    {
        $id = 'block-'.$blockIndex.'-'.str_replace('.', '-', $dataPath);
        $label = e($field['label'] ?? $dataPath);
        $common = 'data-block="'.$blockIndex.'" data-path="'.e($dataPath).'" id="'.e($id).'"';
        $type = $field['type'] ?? 'text';

        return match ($type) {
            'textarea', 'richtext' => $this->textareaField($id, $label, $type, $value, $common),
            'code' => $this->codeField($id, $label, $value, $common),
            'number' => $this->numberField($id, $label, $value, $common),
            'select' => $this->selectField($id, $label, $field, $value, $common),
            'image' => $this->imageField($id, $label, $value, $common),
            'repeater' => $this->repeaterField($blockIndex, $label, $field, $value, $dataPath),
            default => $this->textField($id, $label, $value, $common),
        };
    }

    private function textareaField(string $id, string $label, string $type, mixed $value, string $common): string
    {
        $rows = $type === 'richtext' ? 5 : 3;
        $hint = $type === 'richtext' ? ' <span class="text-muted fw-normal">(HTML مجاز)</span>' : '';

        return '<div class="builder-field"><label for="'.e($id).'">'.$label.$hint.'</label><textarea class="form-control block-field" rows="'.$rows.'" '.$common.'>'.e($this->scalar($value)).'</textarea></div>';
    }

    private function codeField(string $id, string $label, mixed $value, string $common): string
    {
        return '<div class="builder-field"><label for="'.e($id).'">'.$label.'</label><textarea class="form-control block-field font-monospace" rows="6" dir="ltr" '.$common.'>'.e($this->scalar($value)).'</textarea></div>';
    }

    private function numberField(string $id, string $label, mixed $value, string $common): string
    {
        return '<div class="builder-field"><label for="'.e($id).'">'.$label.'</label><input type="number" class="form-control block-field" value="'.e($this->scalar($value)).'" min="1" '.$common.'></div>';
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function selectField(string $id, string $label, array $field, mixed $value, string $common): string
    {
        $options = $field['options'] ?? [];
        $entries = array_is_list($options)
            ? array_map(fn ($option) => [$option, $option], $options)
            : array_map(fn ($optionValue, $optionLabel) => [$optionValue, $optionLabel], array_keys($options), $options);

        $html = '';

        foreach ($entries as [$optionValue, $optionLabel]) {
            $selected = (string) $value === (string) $optionValue ? ' selected' : '';
            $html .= '<option value="'.e($this->scalar($optionValue)).'"'.$selected.'>'.e($this->scalar($optionLabel)).'</option>';
        }

        return '<div class="builder-field"><label for="'.e($id).'">'.$label.'</label><select class="form-select block-field" '.$common.'>'.$html.'</select></div>';
    }

    private function imageField(string $id, string $label, mixed $value, string $common): string
    {
        $text = $this->scalar($value);
        $preview = $text !== ''
            ? '<img src="'.e($text).'" alt="" class="builder-image-preview block-image-preview" data-preview-for="'.e($id).'">'
            : '<img src="" alt="" class="builder-image-preview block-image-preview d-none" data-preview-for="'.e($id).'">';

        return '<div class="builder-field"><label for="'.e($id).'">'.$label.'</label><input type="url" class="form-control block-field" value="'.e($text).'" placeholder="/images/example.jpg" dir="ltr" '.$common.'>'.$preview.'</div>';
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function repeaterField(int $blockIndex, string $label, array $field, mixed $value, string $dataPath): string
    {
        $items = is_array($value) ? array_values($value) : [];
        $subFields = $field['fields'] ?? [];
        $itemsHtml = '';

        foreach ($items as $itemIndex => $item) {
            $itemsHtml .= $this->renderRepeaterItem($blockIndex, $dataPath.'.'.$itemIndex, $subFields, is_array($item) ? $item : []);
        }

        return '<div class="builder-field"><label>'.$label.'</label><div class="repeater-items" data-repeater-path="'.e($dataPath).'">'.$itemsHtml.'</div><button type="button" class="btn btn-sm btn-outline-primary mt-1" data-add-repeater="'.$blockIndex.'" data-repeater-path="'.e($dataPath).'"><i class="ti ti-plus"></i> افزودن مورد</button></div>';
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $item
     */
    private function renderRepeaterItem(int $blockIndex, string $dataPath, array $fields, array $item): string
    {
        $fieldsHtml = '';

        foreach ($fields as $key => $field) {
            $value = array_key_exists($key, $item) ? $item[$key] : $this->defaultForField($field);
            $fieldsHtml .= $this->renderField($blockIndex, $field, $value, $dataPath.'.'.$key);
        }

        return '<div class="builder-repeater-item" data-repeater-item="'.e($dataPath).'"><button type="button" class="btn btn-sm btn-icon btn-outline-danger btn-remove-item" data-remove-repeater="'.$blockIndex.'" data-repeater-path="'.e($dataPath).'" title="حذف"><i class="ti ti-trash"></i></button>'.$fieldsHtml.'</div>';
    }

    private function textField(string $id, string $label, mixed $value, string $common): string
    {
        return '<div class="builder-field"><label for="'.e($id).'">'.$label.'</label><input type="text" class="form-control block-field" value="'.e($this->scalar($value)).'" '.$common.'></div>';
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, array<string, mixed>>  $defs
     */
    private function renderColumns(int $blockIndex, array $block, array $defs): string
    {
        $columns = $block['settings']['columns'] ?? [];
        $columnsHtml = '';
        $nestable = array_values(array_filter(array_keys($defs), fn (string $type) => $type !== 'columns'));

        foreach ($columns as $colIdx => $column) {
            $sectionsHtml = '';

            foreach ($column['sections'] ?? [] as $secIdx => $section) {
                $nestedHtml = '';

                foreach ($section['blocks'] ?? [] as $nestIdx => $nestedBlock) {
                    $nestedHtml .= $this->renderNestedCard($blockIndex, (int) $colIdx, (int) $secIdx, (int) $nestIdx, $nestedBlock, $defs);
                }

                if ($nestedHtml === '') {
                    $nestedHtml = '<p class="text-muted small mb-2">هنوز بلوکی اضافه نشده.</p>';
                }

                $sectionsHtml .= '<div class="layout-section"><div class="layout-section-title">قسمت '.($secIdx + 1).'</div>'.$nestedHtml.$this->renderSectionAdd($blockIndex, (int) $colIdx, (int) $secIdx, $nestable, $defs).'</div>';
            }

            $disabled = count($columns) <= 1 ? ' disabled' : '';
            $columnsHtml .= '<div class="layout-column"><div class="layout-column-header"><span>ستون '.($colIdx + 1).'</span><button type="button" class="btn btn-sm btn-outline-danger" data-remove-column="'.$blockIndex.'" data-col="'.$colIdx.'"'.$disabled.'><i class="ti ti-trash"></i> حذف ستون</button></div>'.$sectionsHtml.'</div>';
        }

        return '<div class="layout-columns-builder"><p class="text-muted small mb-3">هر ستون ۴ قسمت دارد. در هر قسمت می‌توانید یک یا چند بلوک اضافه کنید.</p>'.$columnsHtml.'<button type="button" class="btn btn-sm btn-primary" data-add-column="'.$blockIndex.'"><i class="ti ti-columns"></i> افزودن ستون</button></div>';
    }

    /**
     * @param  array<string, mixed>  $nestedBlock
     * @param  array<string, array<string, mixed>>  $defs
     */
    private function renderNestedCard(int $blockIndex, int $colIdx, int $secIdx, int $nestIdx, array $nestedBlock, array $defs): string
    {
        $type = (string) ($nestedBlock['type'] ?? '');
        $label = e($defs[$type]['label'] ?? $type);
        $key = $blockIndex.'-'.$colIdx.'-'.$secIdx.'-'.$nestIdx;
        $basePath = 'columns.'.$colIdx.'.sections.'.$secIdx.'.blocks.'.$nestIdx.'.settings';
        $body = $this->renderSchemaFields($blockIndex, $defs[$type]['schema'] ?? [], $nestedBlock['settings'] ?? [], $basePath);

        return '<div class="layout-nested-block" data-nested="'.$key.'"><div class="layout-nested-header" data-toggle-nested="'.$key.'"><i class="ti ti-box text-primary"></i><span class="flex-grow-1 small fw-semibold">'.$label.'</span><div class="btn-group btn-group-sm"><button type="button" class="btn btn-outline-secondary" data-move-nested-up="'.$blockIndex.'" data-col="'.$colIdx.'" data-sec="'.$secIdx.'" data-nest="'.$nestIdx.'" title="بالا"><i class="ti ti-arrow-up"></i></button><button type="button" class="btn btn-outline-secondary" data-move-nested-down="'.$blockIndex.'" data-col="'.$colIdx.'" data-sec="'.$secIdx.'" data-nest="'.$nestIdx.'" title="پایین"><i class="ti ti-arrow-down"></i></button><button type="button" class="btn btn-outline-danger" data-remove-nested="'.$blockIndex.'" data-col="'.$colIdx.'" data-sec="'.$secIdx.'" data-nest="'.$nestIdx.'" title="حذف"><i class="ti ti-trash"></i></button></div><i class="ti ti-chevron-down"></i></div><div class="layout-nested-body">'.$body.'</div></div>';
    }

    /**
     * @param  array<int, string>  $nestable
     * @param  array<string, array<string, mixed>>  $defs
     */
    private function renderSectionAdd(int $blockIndex, int $colIdx, int $secIdx, array $nestable, array $defs): string
    {
        $options = '<option value="">— انتخاب بلوک —</option>';

        foreach ($nestable as $type) {
            $options .= '<option value="'.e($type).'">'.e($defs[$type]['label'] ?? $type).'</option>';
        }

        return '<div class="layout-add-block"><select class="form-select form-select-sm" data-nested-type-select="'.$blockIndex.'" data-col="'.$colIdx.'" data-sec="'.$secIdx.'">'.$options.'</select><button type="button" class="btn btn-sm btn-outline-primary" data-add-nested-block="'.$blockIndex.'" data-col="'.$colIdx.'" data-sec="'.$secIdx.'"><i class="ti ti-plus"></i> افزودن</button></div>';
    }

    private function scalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return is_string($value) ? $value : '';
    }
}
