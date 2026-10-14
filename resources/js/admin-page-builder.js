const config = window.pageBuilderConfig;
let builderContent = config.builderContent;
const canvas = document.getElementById('builder-canvas');
const blockDefs = config.blockDefs;
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
                    <input type="text" class="form-control block-field" value="${escapeHtml(value)}" placeholder="/images/example.jpg" dir="ltr" ${common}>
                    ${value ? `<img src="${escapeHtml(value)}" alt="" class="builder-image-preview block-image-preview" data-preview-for="${id}">` : `<img src="" alt="" class="builder-image-preview block-image-preview d-none" data-preview-for="${id}">`}
                </div>`;
        case 'repeater': {
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
        }
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
    if (block.type === 'columns') return renderColumnsLayout(blockIndex, block);

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

function getSchemaField(blockType, path) {
    return blockDefs[blockType]?.schema?.[path.split('.')[0]];
}

document.getElementById('block-palette').addEventListener('click', event => {
    const button = event.target.closest('[data-add-block]');
    if (!button) return;

    const type = button.dataset.addBlock;
    let settings = getDefaultSettings(type);
    if (type === 'columns') settings = normalizeColumnsSettings(settings);
    builderContent.blocks.push({ type, settings });
    renderCanvas();
});

canvas.addEventListener('input', event => {
    const field = event.target.closest('.block-field');
    if (!field) return;

    const blockIndex = Number(field.dataset.block);
    const path = field.dataset.path;
    const value = field.type === 'number' ? (field.value === '' ? 0 : Number(field.value)) : field.value;
    setNestedValue(builderContent.blocks[blockIndex].settings, path, value);
    syncInput();

    const preview = field.closest('.builder-field')?.querySelector('.block-image-preview');
    if (preview) {
        preview.src = value || '';
        preview.classList.toggle('d-none', !value);
    }
});

canvas.addEventListener('change', event => {
    const field = event.target.closest('.block-field');
    if (!field) return;
    setNestedValue(builderContent.blocks[Number(field.dataset.block)].settings, field.dataset.path, field.value);
    syncInput();
});

canvas.addEventListener('click', event => {
    const toggle = event.target.closest('[data-toggle-block]');
    if (toggle && !event.target.closest('button')) {
        toggle.closest('.builder-block').classList.toggle('collapsed');
        return;
    }

    const remove = event.target.closest('[data-remove]');
    if (remove) {
        builderContent.blocks.splice(Number(remove.dataset.remove), 1);
        renderCanvas();
        return;
    }

    const moveUp = event.target.closest('[data-move-up]');
    if (moveUp) {
        const index = Number(moveUp.dataset.moveUp);
        if (index > 0) {
            [builderContent.blocks[index - 1], builderContent.blocks[index]] = [builderContent.blocks[index], builderContent.blocks[index - 1]];
            renderCanvas();
        }
        return;
    }

    const moveDown = event.target.closest('[data-move-down]');
    if (moveDown) {
        const index = Number(moveDown.dataset.moveDown);
        if (index < builderContent.blocks.length - 1) {
            [builderContent.blocks[index + 1], builderContent.blocks[index]] = [builderContent.blocks[index], builderContent.blocks[index + 1]];
            renderCanvas();
        }
        return;
    }

    const addRepeater = event.target.closest('[data-add-repeater]');
    if (addRepeater) {
        const blockIndex = Number(addRepeater.dataset.addRepeater);
        const path = addRepeater.dataset.repeaterPath;
        const items = getNestedValue(builderContent.blocks[blockIndex].settings, path) || [];
        const schemaField = getSchemaField(builderContent.blocks[blockIndex].type, path);
        items.push(defaultRepeaterItem(schemaField?.fields || {}));
        setNestedValue(builderContent.blocks[blockIndex].settings, path, items);
        renderCanvas();
        return;
    }

    const removeRepeater = event.target.closest('[data-remove-repeater]');
    if (removeRepeater) {
        const blockIndex = Number(removeRepeater.dataset.removeRepeater);
        const path = removeRepeater.dataset.repeaterPath;
        const parentPath = path.split('.').slice(0, -1).join('.');
        const itemIndex = Number(path.split('.').pop());
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
        builderContent.blocks[Number(addColumn.dataset.addColumn)].settings.columns.push(emptyColumn());
        renderCanvas();
        return;
    }

    const removeColumn = event.target.closest('[data-remove-column]');
    if (removeColumn && !removeColumn.disabled) {
        builderContent.blocks[Number(removeColumn.dataset.removeColumn)].settings.columns.splice(Number(removeColumn.dataset.col), 1);
        renderCanvas();
        return;
    }

    const addNested = event.target.closest('[data-add-nested-block]');
    if (addNested) {
        const blockIndex = Number(addNested.dataset.addNestedBlock);
        const colIndex = Number(addNested.dataset.col);
        const sectionIndex = Number(addNested.dataset.sec);
        const select = addNested.parentElement.querySelector('[data-nested-type-select]');
        const type = select?.value;
        if (!type) {
            window.alert('لطفاً نوع بلوک را انتخاب کنید.');
            return;
        }

        const path = `columns.${colIndex}.sections.${sectionIndex}.blocks`;
        const blocks = getNestedValue(builderContent.blocks[blockIndex].settings, path) || [];
        blocks.push({ type, settings: getDefaultSettings(type) });
        setNestedValue(builderContent.blocks[blockIndex].settings, path, blocks);
        renderCanvas();
        return;
    }

    const removeNested = event.target.closest('[data-remove-nested]');
    if (removeNested) {
        const blockIndex = Number(removeNested.dataset.removeNested);
        const path = `columns.${removeNested.dataset.col}.sections.${removeNested.dataset.sec}.blocks`;
        const blocks = getNestedValue(builderContent.blocks[blockIndex].settings, path) || [];
        blocks.splice(Number(removeNested.dataset.nest), 1);
        setNestedValue(builderContent.blocks[blockIndex].settings, path, blocks);
        renderCanvas();
        return;
    }

    for (const direction of ['up', 'down']) {
        const moveNested = event.target.closest(`[data-move-nested-${direction}]`);
        if (!moveNested) continue;
        const blockIndex = Number(moveNested.dataset[`moveNested${direction === 'up' ? 'Up' : 'Down'}`]);
        const path = `columns.${moveNested.dataset.col}.sections.${moveNested.dataset.sec}.blocks`;
        const blocks = getNestedValue(builderContent.blocks[blockIndex].settings, path) || [];
        const index = Number(moveNested.dataset.nest);
        const other = direction === 'up' ? index - 1 : index + 1;
        if (other >= 0 && other < blocks.length) {
            [blocks[index], blocks[other]] = [blocks[other], blocks[index]];
            setNestedValue(builderContent.blocks[blockIndex].settings, path, blocks);
            renderCanvas();
        }
        return;
    }
});

form.addEventListener('submit', event => {
    try {
        canvas.querySelectorAll('.block-field').forEach(field => {
            const blockIndex = Number(field.dataset.block);
            const path = field.dataset.path;
            const settings = builderContent.blocks?.[blockIndex]?.settings;
            if (!path || !settings) return;
            const value = field.type === 'number' ? (field.value === '' ? 0 : Number(field.value)) : field.value;
            setNestedValue(settings, path, value);
        });

        syncInput();
        if (!contentInput.value) {
            event.preventDefault();
            window.alert('محتوای صفحه برای ذخیره خالی است.');
            return;
        }

        if (event.submitter) {
            event.submitter.disabled = true;
            event.submitter.textContent = 'در حال ذخیره...';
        }
    } catch (error) {
        event.preventDefault();
        window.alert('ذخیره انجام نشد. صفحه را یک‌بار تازه کنید و دوباره ذخیره کنید.');
    }
});

document.getElementById('preview-builder').addEventListener('click', async () => {
    syncInput();
    const preview = document.getElementById('builder-preview');
    preview.innerHTML = '<p class="text-muted small">در حال بارگذاری...</p>';

    try {
        const response = await fetch(config.previewUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ builder_content: builderContent }),
        });
        if (!response.ok) {
            preview.innerHTML = '<p class="text-danger small">خطا در پیش‌نمایش</p>';
            return;
        }

        const data = await response.json();
        preview.innerHTML = data.html || '<p class="text-muted small">محتوایی برای نمایش نیست.</p>';
    } catch (error) {
        preview.innerHTML = '<p class="text-danger small">خطا در ارتباط با سرور</p>';
    }
});

if (!Array.isArray(builderContent.blocks)) builderContent.blocks = [];
if (Array.isArray(builderContent.blocks)) {
    builderContent.blocks = builderContent.blocks.map(block => {
        if (block?.type === 'columns') {
            block.settings = normalizeColumnsSettings(block.settings || {});
        }
        return block;
    });
}

renderCanvas();
