@extends('layouts.admin')
@section('title', 'صفحه‌ساز — '.$page->title)
@section('content')
<style>
    .builder-block { border: 1px solid var(--bs-border-color); border-radius: .5rem; margin-bottom: .75rem; background: var(--bs-body-bg); }
    .builder-block-header { display: flex; align-items: center; gap: .5rem; padding: .75rem 1rem; cursor: pointer; user-select: none; }
    .builder-block-header:hover { background: rgba(0,0,0,.03); }
    .builder-block-body { padding: 0 1rem 1rem; border-top: 1px solid var(--bs-border-color); }
    .builder-block.collapsed .builder-block-body { display: none; }
    .builder-field { margin-bottom: .75rem; }
    .builder-field label { font-size: .8125rem; font-weight: 600; margin-bottom: .25rem; display: block; }
    .builder-repeater-item { border: 1px dashed var(--bs-border-color); border-radius: .375rem; padding: .75rem; margin-bottom: .5rem; position: relative; }
    .builder-repeater-item .btn-remove-item { position: absolute; top: .5rem; left: .5rem; }
    .builder-image-preview { max-height: 80px; max-width: 100%; margin-top: .5rem; border-radius: .375rem; }
    .builder-empty { text-align: center; padding: 2rem; color: var(--bs-secondary-color); border: 2px dashed var(--bs-border-color); border-radius: .5rem; }
    #builder-preview { max-height: 70vh; overflow: auto; }
    #builder-preview .cms-block { font-size: .875rem; }
    .layout-column { border: 1px solid var(--bs-border-color); border-radius: .5rem; padding: .75rem; margin-bottom: .75rem; background: rgba(0,0,0,.015); }
    .layout-column-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .75rem; font-weight: 600; }
    .layout-section { border: 1px dashed var(--bs-border-color); border-radius: .375rem; padding: .75rem; margin-bottom: .5rem; background: var(--bs-body-bg); }
    .layout-section-title { font-size: .75rem; font-weight: 700; text-transform: uppercase; color: var(--bs-secondary-color); margin-bottom: .5rem; }
    .layout-nested-block { border: 1px solid var(--bs-border-color); border-radius: .375rem; margin-bottom: .5rem; }
    .layout-nested-header { display: flex; align-items: center; gap: .5rem; padding: .5rem .75rem; background: rgba(0,0,0,.03); cursor: pointer; }
    .layout-nested-body { padding: .75rem; border-top: 1px solid var(--bs-border-color); }
    .layout-nested-block.collapsed .layout-nested-body { display: none; }
    .layout-add-block { display: flex; gap: .5rem; align-items: center; margin-top: .5rem; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">صفحه‌ساز: {{ $page->title }}</h4>
    <div class="d-flex gap-2">
        @if($page->slug && ! $page->is_system)
            <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="btn btn-outline-secondary">مشاهده صفحه</a>
        @endif
        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-outline-secondary">تنظیمات صفحه</a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('admin.pages.builder.save', $page) }}" id="builder-form">
    @csrf
    <input type="hidden" name="builder_content" id="builder-content-input" value="">
    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 5rem; z-index: 2;">
                <div class="card-header fw-semibold">افزودن بلوک</div>
                <div class="list-group list-group-flush" id="block-palette">
                    @foreach($blocks as $block)
                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2" data-add-block="{{ $block['type'] }}">
                            <i class="ti ti-layout-grid-add text-success"></i>
                            {{ $block['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">محتوای صفحه</span>
                    <span class="badge bg-label-primary" id="block-count">۰ بلوک</span>
                </div>
                <div class="card-body" id="builder-canvas"></div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="ti ti-device-floppy me-1"></i> ذخیره صفحه
                </button>
                <button type="button" class="btn btn-outline-primary" id="preview-builder">
                    <i class="ti ti-eye me-1"></i> پیش‌نمایش
                </button>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 5rem; z-index: 2;">
                <div class="card-header fw-semibold">پیش‌نمایش زنده</div>
                <div class="card-body" id="builder-preview">
                    <p class="text-muted small mb-0">روی «پیش‌نمایش» کلیک کنید.</p>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let builderContent = @json($builderContent);
const canvas = document.getElementById('builder-canvas');
const blockDefs = @json(collect($blocks)->keyBy('type'));
const form = document.getElementById('builder-form');
const contentInput = document.getElementById('builder-content-input');
const blockCountEl = document.getElementById('block-count');
const SECTIONS_PER_COLUMN = 4;
const nestableBlockTypes = Object.keys(blockDefs).filter(type => type !== 'columns');

function emptyColumn() {
    return { sections: Array.from({ length: SECTIONS_PER_COLUMN }, () => ({ blocks: [] })) };
}

function normalizeColumnsSettings(settings) {
    const columns = (settings?.columns || []).map(column => {
        if (column.content !== undefined && !column.sections) {
            const sections = Array.from({ length: SECTIONS_PER_COLUMN }, (_, index) => ({
                blocks: index === 0 && column.content
                    ? [{ type: 'text', settings: { content: column.content } }]
                    : [],
            }));
            return { sections };
        }

        const sections = [...(column.sections || [])];
        while (sections.length < SECTIONS_PER_COLUMN) {
            sections.push({ blocks: [] });
        }

        sections.forEach(section => {
            section.blocks = section.blocks || [];
        });

        return { sections: sections.slice(0, SECTIONS_PER_COLUMN) };
    });

    return { columns: columns.length ? columns : [emptyColumn(), emptyColumn()] };
}

function syncInput() {
    contentInput.value = JSON.stringify(builderContent);
    blockCountEl.textContent = `${(builderContent.blocks || []).length} بلوک`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function getNestedValue(obj, path) {
    return path.split('.').reduce((current, key) => current?.[key], obj);
}

function setNestedValue(obj, path, value) {
    const keys = path.split('.');
    let current = obj;
    for (let i = 0; i < keys.length - 1; i++) {
        const key = keys[i];
        if (current[key] == null) {
            current[key] = Number.isInteger(+keys[i + 1]) ? [] : {};
        }
        current = current[key];
    }
    current[keys[keys.length - 1]] = value;
}

function defaultForField(field) {
    if (field.default !== undefined) return structuredClone(field.default);
    if (field.type === 'repeater') return [];
    if (field.type === 'number') return 0;
    return '';
}

function getDefaultSettings(type) {
    const def = blockDefs[type];
    if (!def) return {};

    if (def.defaults && Object.keys(def.defaults).length) {
        return structuredClone(def.defaults);
    }

    const settings = {};
    Object.entries(def.schema || {}).forEach(([key, field]) => {
        settings[key] = defaultForField(field);
    });
    return settings;
}

function defaultRepeaterItem(fields) {
    const item = {};
    Object.entries(fields || {}).forEach(([key, field]) => {
        item[key] = defaultForField(field);
    });
    return item;
}

function renderSelectOptions(field, value) {
    const options = field.options || {};
    const entries = Array.isArray(options)
        ? options.map(option => [option, option])
        : Object.entries(options);

    return entries.map(([optionValue, optionLabel]) =>
        `<option value="${escapeHtml(optionValue)}"${String(value) === String(optionValue) ? ' selected' : ''}>${escapeHtml(optionLabel)}</option>`
    ).join('');
}

function renderFieldInput(blockIndex, fieldKey, field, value, dataPath) {
    const id = `block-${blockIndex}-${dataPath.replace(/\./g, '-')}`;
    const label = escapeHtml(field.label || fieldKey);
    const common = `data-block="${blockIndex}" data-path="${dataPath}" id="${id}"`;

    switch (field.type) {
        case 'textarea':
        case 'richtext':
            return `
                <div class="builder-field">
                    <label for="${id}">${label}${field.type === 'richtext' ? ' <span class="text-muted fw-normal">(HTML مجاز)</span>' : ''}</label>
                    <textarea class="form-control block-field" rows="${field.type === 'richtext' ? 5 : 3}" ${common}>${escapeHtml(value)}</textarea>
                </div>`;
        case 'code':
            return `
                <div class="builder-field">
                    <label for="${id}">${label}</label>
                    <textarea class="form-control block-field font-monospace" rows="6" dir="ltr" ${common}>${escapeHtml(value)}</textarea>
                </div>`;
        case 'number':
            return `
                <div class="builder-field">
                    <label for="${id}">${label}</label>
                    <input type="number" class="form-control block-field" value="${escapeHtml(value)}" min="1" ${common}>
                </div>`;
        case 'select':
            return `
                <div class="builder-field">
                    <label for="${id}">${label}</label>
                    <select class="form-select block-field" ${common}>${renderSelectOptions(field, value)}</select>
                </div>`;
        case 'image':
            return `
                <div class="builder-field">
                    <label for="${id}">${label}</label>
                    <input type="url" class="form-control block-field" value="${escapeHtml(value)}" placeholder="/images/example.jpg" dir="ltr" ${common}>
                    ${value ? `<img src="${escapeHtml(value)}" alt="" class="builder-image-preview block-image-preview" data-preview-for="${id}">` : `<img src="" alt="" class="builder-image-preview block-image-preview d-none" data-preview-for="${id}">`}
                </div>`;
        case 'repeater':
            const items = Array.isArray(value) ? value : [];
            const subFields = field.fields || {};
            const itemsHtml = items.map((item, itemIndex) => renderRepeaterItem(blockIndex, `${dataPath}.${itemIndex}`, subFields, item)).join('');
            return `
                <div class="builder-field">
                    <label>${label}</label>
                    <div class="repeater-items" data-repeater-path="${dataPath}">${itemsHtml}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" data-add-repeater="${blockIndex}" data-repeater-path="${dataPath}">
                        <i class="ti ti-plus"></i> افزودن مورد
                    </button>
                </div>`;
        default:
            return `
                <div class="builder-field">
                    <label for="${id}">${label}</label>
                    <input type="text" class="form-control block-field" value="${escapeHtml(value)}" ${common}>
                </div>`;
    }
}

function renderRepeaterItem(blockIndex, dataPath, fields, item) {
    const fieldsHtml = Object.entries(fields).map(([key, field]) => {
        const value = item?.[key] ?? defaultForField(field);
        return renderFieldInput(blockIndex, key, field, value, `${dataPath}.${key}`);
    }).join('');

    return `
        <div class="builder-repeater-item" data-repeater-item="${dataPath}">
            <button type="button" class="btn btn-sm btn-icon btn-outline-danger btn-remove-item" data-remove-repeater="${blockIndex}" data-repeater-path="${dataPath}" title="حذف">
                <i class="ti ti-trash"></i>
            </button>
            ${fieldsHtml}
        </div>`;
}

function renderNestedBlockFields(blockIndex, colIdx, secIdx, nestIdx, nestedBlock) {
    const schema = blockDefs[nestedBlock.type]?.schema || {};
    const settings = nestedBlock.settings || {};
    const basePath = `columns.${colIdx}.sections.${secIdx}.blocks.${nestIdx}.settings`;

    return Object.entries(schema).map(([key, field]) => {
        const value = settings[key] ?? defaultForField(field);
        return renderFieldInput(blockIndex, key, field, value, `${basePath}.${key}`);
    }).join('');
}

function renderNestedBlockCard(blockIndex, colIdx, secIdx, nestIdx, nestedBlock) {
    const label = blockDefs[nestedBlock.type]?.label || nestedBlock.type;

    return `
        <div class="layout-nested-block" data-nested="${blockIndex}-${colIdx}-${secIdx}-${nestIdx}">
            <div class="layout-nested-header" data-toggle-nested="${blockIndex}-${colIdx}-${secIdx}-${nestIdx}">
                <i class="ti ti-box text-primary"></i>
                <span class="flex-grow-1 small fw-semibold">${escapeHtml(label)}</span>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" data-move-nested-up="${blockIndex}" data-col="${colIdx}" data-sec="${secIdx}" data-nest="${nestIdx}" title="بالا"><i class="ti ti-arrow-up"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-move-nested-down="${blockIndex}" data-col="${colIdx}" data-sec="${secIdx}" data-nest="${nestIdx}" title="پایین"><i class="ti ti-arrow-down"></i></button>
                    <button type="button" class="btn btn-outline-danger" data-remove-nested="${blockIndex}" data-col="${colIdx}" data-sec="${secIdx}" data-nest="${nestIdx}" title="حذف"><i class="ti ti-trash"></i></button>
                </div>
                <i class="ti ti-chevron-down"></i>
            </div>
            <div class="layout-nested-body">${renderNestedBlockFields(blockIndex, colIdx, secIdx, nestIdx, nestedBlock)}</div>
        </div>`;
}

function renderSectionAddBlock(blockIndex, colIdx, secIdx) {
    const options = nestableBlockTypes.map(type =>
        `<option value="${escapeHtml(type)}">${escapeHtml(blockDefs[type]?.label || type)}</option>`
    ).join('');

    return `
        <div class="layout-add-block">
            <select class="form-select form-select-sm" data-nested-type-select="${blockIndex}" data-col="${colIdx}" data-sec="${secIdx}">
                <option value="">— انتخاب بلوک —</option>
                ${options}
            </select>
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-nested-block="${blockIndex}" data-col="${colIdx}" data-sec="${secIdx}">
                <i class="ti ti-plus"></i> افزودن
            </button>
        </div>`;
}

function renderColumnsLayout(blockIndex, block) {
    const columns = block.settings?.columns || [];

    const columnsHtml = columns.map((column, colIdx) => {
        const sectionsHtml = (column.sections || []).map((section, secIdx) => {
            const nestedBlocks = section.blocks || [];
            const nestedHtml = nestedBlocks.map((nestedBlock, nestIdx) =>
                renderNestedBlockCard(blockIndex, colIdx, secIdx, nestIdx, nestedBlock)
            ).join('');

            return `
                <div class="layout-section">
                    <div class="layout-section-title">قسمت ${secIdx + 1}</div>
                    ${nestedHtml || '<p class="text-muted small mb-2">هنوز بلوکی اضافه نشده.</p>'}
                    ${renderSectionAddBlock(blockIndex, colIdx, secIdx)}
                </div>`;
        }).join('');

        return `
            <div class="layout-column">
                <div class="layout-column-header">
                    <span>ستون ${colIdx + 1}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-column="${blockIndex}" data-col="${colIdx}" ${columns.length <= 1 ? 'disabled' : ''}>
                        <i class="ti ti-trash"></i> حذف ستون
                    </button>
                </div>
                ${sectionsHtml}
            </div>`;
    }).join('');

    return `
        <div class="layout-columns-builder">
            <p class="text-muted small mb-3">هر ستون ۴ قسمت دارد. در هر قسمت می‌توانید یک یا چند بلوک اضافه کنید.</p>
            ${columnsHtml}
            <button type="button" class="btn btn-sm btn-primary" data-add-column="${blockIndex}">
                <i class="ti ti-columns"></i> افزودن ستون
            </button>
        </div>`;
}

function renderBlockSettings(blockIndex, block) {
    if (block.type === 'columns') {
        return renderColumnsLayout(blockIndex, block);
    }

    const schema = blockDefs[block.type]?.schema || {};
    const settings = block.settings || {};

    return Object.entries(schema).map(([key, field]) => {
        const value = settings[key] ?? defaultForField(field);
        return renderFieldInput(blockIndex, key, field, value, key);
    }).join('');
}

function renderCanvas() {
    const blocks = builderContent.blocks || [];

    if (!blocks.length) {
        canvas.innerHTML = '<div class="builder-empty">از سمت راست یک بلوک اضافه کنید.</div>';
        syncInput();
        return;
    }

    canvas.innerHTML = blocks.map((block, index) => {
        const label = blockDefs[block.type]?.label || block.type;
        return `
            <div class="builder-block" data-block-index="${index}">
                <div class="builder-block-header" data-toggle-block="${index}">
                    <i class="ti ti-grip-vertical text-muted"></i>
                    <strong class="flex-grow-1">${escapeHtml(label)}</strong>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" data-move-up="${index}" title="بالا"><i class="ti ti-arrow-up"></i></button>
                        <button type="button" class="btn btn-outline-secondary" data-move-down="${index}" title="پایین"><i class="ti ti-arrow-down"></i></button>
                        <button type="button" class="btn btn-outline-danger" data-remove="${index}" title="حذف"><i class="ti ti-trash"></i></button>
                    </div>
                    <i class="ti ti-chevron-down"></i>
                </div>
                <div class="builder-block-body">${renderBlockSettings(index, block)}</div>
            </div>`;
    }).join('');

    syncInput();
}

document.getElementById('block-palette').addEventListener('click', (event) => {
    const button = event.target.closest('[data-add-block]');
    if (!button) return;

    builderContent.blocks = builderContent.blocks || [];
    const type = button.dataset.addBlock;
    let settings = getDefaultSettings(type);
    if (type === 'columns') {
        settings = normalizeColumnsSettings(settings);
    }
    builderContent.blocks.push({ type, settings });
    renderCanvas();
});

canvas.addEventListener('input', (event) => {
    const field = event.target.closest('.block-field');
    if (!field) return;

    const blockIndex = +field.dataset.block;
    const path = field.dataset.path;
    let value = field.value;

    if (field.type === 'number') {
        value = field.value === '' ? 0 : +field.value;
    }

    setNestedValue(builderContent.blocks[blockIndex].settings, path, value);
    syncInput();

    if (field.type === 'url' && field.closest('.builder-field')?.querySelector('.block-image-preview')) {
        const preview = field.closest('.builder-field').querySelector('.block-image-preview');
        if (value) {
            preview.src = value;
            preview.classList.remove('d-none');
        } else {
            preview.classList.add('d-none');
        }
    }
});

canvas.addEventListener('change', (event) => {
    const field = event.target.closest('.block-field');
    if (!field) return;

    const blockIndex = +field.dataset.block;
    const path = field.dataset.path;
    setNestedValue(builderContent.blocks[blockIndex].settings, path, field.value);
    syncInput();
});

canvas.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-toggle-block]');
    if (toggle && !event.target.closest('button')) {
        toggle.closest('.builder-block').classList.toggle('collapsed');
        return;
    }

    const removeBtn = event.target.closest('[data-remove]');
    if (removeBtn) {
        builderContent.blocks.splice(+removeBtn.dataset.remove, 1);
        renderCanvas();
        return;
    }

    const moveUp = event.target.closest('[data-move-up]');
    if (moveUp) {
        const index = +moveUp.dataset.moveUp;
        if (index > 0) {
            [builderContent.blocks[index - 1], builderContent.blocks[index]] = [builderContent.blocks[index], builderContent.blocks[index - 1]];
            renderCanvas();
        }
        return;
    }

    const moveDown = event.target.closest('[data-move-down]');
    if (moveDown) {
        const index = +moveDown.dataset.moveDown;
        if (index < builderContent.blocks.length - 1) {
            [builderContent.blocks[index + 1], builderContent.blocks[index]] = [builderContent.blocks[index], builderContent.blocks[index + 1]];
            renderCanvas();
        }
        return;
    }

    const addRepeater = event.target.closest('[data-add-repeater]');
    if (addRepeater) {
        const blockIndex = +addRepeater.dataset.addRepeater;
        const path = addRepeater.dataset.repeaterPath;
        const blockType = builderContent.blocks[blockIndex].type;
        const field = blockDefs[blockType]?.schema?.[path.split('.')[0]] || blockDefs[blockType]?.schema?.[path];
        const schemaField = getSchemaField(blockType, path);
        const items = getNestedValue(builderContent.blocks[blockIndex].settings, path) || [];
        items.push(defaultRepeaterItem(schemaField?.fields || {}));
        setNestedValue(builderContent.blocks[blockIndex].settings, path, items);
        renderCanvas();
        return;
    }

    const removeRepeater = event.target.closest('[data-remove-repeater]');
    if (removeRepeater) {
        const blockIndex = +removeRepeater.dataset.removeRepeater;
        const path = removeRepeater.dataset.repeaterPath;
        const parentPath = path.split('.').slice(0, -1).join('.');
        const itemIndex = +path.split('.').pop();
        const items = getNestedValue(builderContent.blocks[blockIndex].settings, parentPath) || [];
        items.splice(itemIndex, 1);
        setNestedValue(builderContent.blocks[blockIndex].settings, parentPath, items);
        renderCanvas();
        return;
    }

    const toggleNested = event.target.closest('[data-toggle-nested]');
    if (toggleNested && !event.target.closest('button')) {
        toggleNested.closest('.layout-nested-block').classList.toggle('collapsed');
        return;
    }

    const addColumn = event.target.closest('[data-add-column]');
    if (addColumn) {
        const blockIndex = +addColumn.dataset.addColumn;
        builderContent.blocks[blockIndex].settings.columns.push(emptyColumn());
        renderCanvas();
        return;
    }

    const removeColumn = event.target.closest('[data-remove-column]');
    if (removeColumn && !removeColumn.disabled) {
        const blockIndex = +removeColumn.dataset.removeColumn;
        const colIdx = +removeColumn.dataset.col;
        builderContent.blocks[blockIndex].settings.columns.splice(colIdx, 1);
        renderCanvas();
        return;
    }

    const addNestedBlock = event.target.closest('[data-add-nested-block]');
    if (addNestedBlock) {
        const blockIndex = +addNestedBlock.dataset.addNestedBlock;
        const colIdx = +addNestedBlock.dataset.col;
        const secIdx = +addNestedBlock.dataset.sec;
        const select = addNestedBlock.previousElementSibling?.matches('[data-nested-type-select]')
            ? addNestedBlock.previousElementSibling
            : addNestedBlock.parentElement?.querySelector('[data-nested-type-select]');
        const type = select?.value;

        if (!type) {
            alert('لطفاً نوع بلوک را انتخاب کنید.');
            return;
        }

        const path = `columns.${colIdx}.sections.${secIdx}.blocks`;
        const blocks = getNestedValue(builderContent.blocks[blockIndex].settings, path) || [];
        blocks.push({ type, settings: getDefaultSettings(type) });
        setNestedValue(builderContent.blocks[blockIndex].settings, path, blocks);
        renderCanvas();
        return;
    }

    const removeNested = event.target.closest('[data-remove-nested]');
    if (removeNested) {
        const blockIndex = +removeNested.dataset.removeNested;
        const colIdx = +removeNested.dataset.col;
        const secIdx = +removeNested.dataset.sec;
        const nestIdx = +removeNested.dataset.nest;
        const path = `columns.${colIdx}.sections.${secIdx}.blocks`;
        const blocks = getNestedValue(builderContent.blocks[blockIndex].settings, path) || [];
        blocks.splice(nestIdx, 1);
        setNestedValue(builderContent.blocks[blockIndex].settings, path, blocks);
        renderCanvas();
        return;
    }

    const moveNestedUp = event.target.closest('[data-move-nested-up]');
    if (moveNestedUp) {
        const blockIndex = +moveNestedUp.dataset.moveNestedUp;
        const colIdx = +moveNestedUp.dataset.col;
        const secIdx = +moveNestedUp.dataset.sec;
        const nestIdx = +moveNestedUp.dataset.nest;
        const path = `columns.${colIdx}.sections.${secIdx}.blocks`;
        const blocks = getNestedValue(builderContent.blocks[blockIndex].settings, path) || [];
        if (nestIdx > 0) {
            [blocks[nestIdx - 1], blocks[nestIdx]] = [blocks[nestIdx], blocks[nestIdx - 1]];
            setNestedValue(builderContent.blocks[blockIndex].settings, path, blocks);
            renderCanvas();
        }
        return;
    }

    const moveNestedDown = event.target.closest('[data-move-nested-down]');
    if (moveNestedDown) {
        const blockIndex = +moveNestedDown.dataset.moveNestedDown;
        const colIdx = +moveNestedDown.dataset.col;
        const secIdx = +moveNestedDown.dataset.sec;
        const nestIdx = +moveNestedDown.dataset.nest;
        const path = `columns.${colIdx}.sections.${secIdx}.blocks`;
        const blocks = getNestedValue(builderContent.blocks[blockIndex].settings, path) || [];
        if (nestIdx < blocks.length - 1) {
            [blocks[nestIdx + 1], blocks[nestIdx]] = [blocks[nestIdx], blocks[nestIdx + 1]];
            setNestedValue(builderContent.blocks[blockIndex].settings, path, blocks);
            renderCanvas();
        }
    }
});

function getSchemaField(blockType, path) {
    const topKey = path.split('.')[0];
    return blockDefs[blockType]?.schema?.[topKey];
}

form.addEventListener('submit', () => syncInput());

document.getElementById('preview-builder').addEventListener('click', async () => {
    syncInput();
    const previewEl = document.getElementById('builder-preview');
    previewEl.innerHTML = '<p class="text-muted small">در حال بارگذاری...</p>';

    const response = await fetch('{{ route('admin.pages.builder.preview', $page) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ builder_content: builderContent }),
    });

    if (!response.ok) {
        previewEl.innerHTML = '<p class="text-danger small">خطا در پیش‌نمایش</p>';
        return;
    }

    const data = await response.json();
    previewEl.innerHTML = data.html || '<p class="text-muted small">محتوایی برای نمایش نیست.</p>';
});

if (!builderContent.blocks?.length) {
    builderContent.blocks = [];
} else {
    builderContent.blocks = builderContent.blocks.map(block => {
        const merged = { ...getDefaultSettings(block.type), ...(block.settings || {}) };

        if (block.type === 'columns') {
            return { type: block.type, settings: normalizeColumnsSettings(merged) };
        }

        return { type: block.type, settings: merged };
    });
}

renderCanvas();
</script>
@endsection
