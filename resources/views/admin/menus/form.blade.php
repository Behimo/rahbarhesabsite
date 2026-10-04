@extends('layouts.admin')
@section('title', $menu->exists ? 'ویرایش منو' : 'منوی جدید')
@section('heading', $menu->exists ? 'ویرایش: '.$menu->name : 'منوی جدید')
@section('content')
<form method="POST" action="{{ $menu->exists ? route('admin.menus.update', $menu) : route('admin.menus.store') }}" class="card mb-4">
    @csrf @if($menu->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label">نام</label><input name="name" class="form-control" value="{{ old('name', $menu->name) }}" required></div>
        <div class="col-md-4"><label class="form-label">Slug</label><input name="slug" class="form-control" dir="ltr" value="{{ old('slug', $menu->slug) }}" required></div>
        <div class="col-md-4">
            <label class="form-label">محل نمایش</label>
            <input name="location" class="form-control" value="{{ old('location', $menu->location) }}" placeholder="primary">
            <div class="form-text">برای نوار اصلی سایت مقدار <code>primary</code> را بگذارید.</div>
        </div>
        <div class="col-12 admin-form-actions"><button class="btn btn-primary">ذخیره مشخصات</button></div>
    </div>
</form>

@if($menu->exists)
<style>
    .menu-builder-tree { display: flex; flex-direction: column; gap: .75rem; }
    .menu-node { border: 1px solid var(--bs-border-color); border-radius: .75rem; background: #fff; padding: .75rem; }
    .menu-node.is-dragging { opacity: .45; }
    .menu-node.is-drop-before { box-shadow: inset 0 3px 0 var(--bs-primary); }
    .menu-node.is-drop-after { box-shadow: inset 0 -3px 0 var(--bs-primary); }
    .menu-node.is-drop-child { outline: 2px dashed var(--bs-primary); outline-offset: -2px; }
    .menu-node-head { display: grid; grid-template-columns: auto 1fr; gap: .75rem; align-items: start; }
    .menu-node-handle { width: 36px; height: 36px; border: 1px solid var(--bs-border-color); border-radius: .5rem; background: #f8f7fa; color: #6f6b7d; cursor: grab; }
    .menu-node-fields { display: grid; grid-template-columns: 1.2fr .8fr 1.2fr .7fr; gap: .5rem; }
    .menu-node-actions { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .menu-node-children { margin-top: .75rem; margin-right: 1.25rem; padding-right: .75rem; border-right: 2px solid rgba(115, 103, 240, .28); display: flex; flex-direction: column; gap: .75rem; }
    .menu-node-children:empty { display: none; }
    .menu-builder-empty { padding: 1.5rem; text-align: center; color: #6f6b7d; border: 1px dashed var(--bs-border-color); border-radius: .75rem; }
    @media (max-width: 991px) {
        .menu-node-fields { grid-template-columns: 1fr; }
    }
</style>
<div class="card" id="menu-builder">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">آیتم‌های منو</h5>
        <button type="button" class="btn btn-sm btn-primary" id="add-menu-item">افزودن آیتم</button>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">هر آیتم می‌تواند زیرمنو داشته باشد. برای جابه‌جایی دستگیره را بکشید. رها کردن روی وسط یک آیتم، آن را فرزند همان آیتم می‌کند.</p>
        <div id="menu-tree" class="menu-builder-tree"></div>
        <div class="d-flex align-items-center gap-3 mt-3">
            <button type="button" class="btn btn-success" id="save-menu-tree">ذخیره ساختار منو</button>
            <span id="menu-tree-status" class="small"></span>
        </div>
    </div>
</div>
@endif
@endsection

@if($menu->exists)
@section('page-script')
<script>
const initialTree = @json($tree ?? []);
const linkSources = @json($linkSources ?? ['pages' => [], 'posts' => [], 'courses' => []]);
const saveUrl = @json(route('admin.menus.tree', $menu));
const treeEl = document.getElementById('menu-tree');
const statusEl = document.getElementById('menu-tree-status');
let tree = (Array.isArray(initialTree) ? initialTree : []).map(normalizeNode);
let dragItem = null;

function normalizeNode(raw) {
    const meta = raw.meta && typeof raw.meta === 'object' ? raw.meta : {};
    return {
        label: raw.label || '',
        type: raw.type || 'custom',
        url: raw.url || '',
        route_name: raw.route_name || '',
        route_params: raw.route_params || null,
        target: raw.target === '_blank' ? '_blank' : '_self',
        meta: { slug: meta.slug || '' },
        children: Array.isArray(raw.children) ? raw.children.map(normalizeNode) : [],
    };
}

function blankItem() {
    return normalizeNode({ label: 'آیتم جدید', type: 'custom', url: '/' });
}

function clearDropClasses() {
    treeEl.querySelectorAll('.is-drop-before, .is-drop-after, .is-drop-child').forEach((el) => {
        el.classList.remove('is-drop-before', 'is-drop-after', 'is-drop-child');
    });
}

function containsItem(ancestor, node) {
    return (ancestor.children || []).some((child) => child === node || containsItem(child, node));
}

function detach(item, nodes) {
    const index = nodes.indexOf(item);
    if (index !== -1) {
        nodes.splice(index, 1);
        return true;
    }
    return nodes.some((node) => detach(item, node.children || []));
}

function moveItem(target, list, position) {
    if (!dragItem || dragItem === target || containsItem(dragItem, target)) {
        return;
    }
    const moving = dragItem;
    dragItem = null;
    detach(moving, tree);
    if (position === 'child') {
        target.children.push(moving);
    } else {
        const index = list.indexOf(target);
        const at = index === -1 ? list.length : (position === 'before' ? index : index + 1);
        list.splice(at, 0, moving);
    }
    renderTree();
}

function renderTree() {
    treeEl.replaceChildren();
    if (tree.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'menu-builder-empty';
        empty.textContent = 'هنوز آیتمی نیست. با «افزودن آیتم» شروع کنید.';
        treeEl.appendChild(empty);
        return;
    }
    tree.forEach((item, index) => treeEl.appendChild(renderItem(item, tree, index)));
}

function renderItem(item, siblings, index) {
    const node = document.createElement('div');
    node.className = 'menu-node';

    const head = document.createElement('div');
    head.className = 'menu-node-head';

    const handle = document.createElement('button');
    handle.type = 'button';
    handle.className = 'menu-node-handle';
    handle.title = 'جابه‌جایی';
    handle.innerHTML = '<i class="ti ti-grip-vertical"></i>';
    handle.addEventListener('mousedown', () => { node.draggable = true; });

    const body = document.createElement('div');
    const fields = document.createElement('div');
    fields.className = 'menu-node-fields';

    const label = document.createElement('input');
    label.className = 'form-control';
    label.placeholder = 'برچسب';
    label.value = item.label;
    label.addEventListener('input', () => { item.label = label.value; });

    const type = document.createElement('select');
    type.className = 'form-select';
    [
        ['custom', 'لینک سفارشی'],
        ['route', 'مسیر داخلی'],
        ['page', 'برگه'],
        ['post', 'نوشته'],
        ['course', 'دوره'],
    ].forEach(([value, text]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = text;
        option.selected = item.type === value;
        type.appendChild(option);
    });

    const destination = document.createElement('div');
    const target = document.createElement('select');
    target.className = 'form-select';
    [['_self', 'همان پنجره'], ['_blank', 'تب جدید']].forEach(([value, text]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = text;
        option.selected = item.target === value;
        target.appendChild(option);
    });
    target.addEventListener('change', () => { item.target = target.value; });

    function paintDestination() {
        destination.replaceChildren();
        if (item.type === 'route') {
            const input = document.createElement('input');
            input.className = 'form-control';
            input.dir = 'ltr';
            input.placeholder = 'route name';
            input.value = item.route_name || '';
            input.addEventListener('input', () => { item.route_name = input.value; });
            destination.appendChild(input);
            return;
        }
        if (item.type === 'page' || item.type === 'post' || item.type === 'course') {
            const select = document.createElement('select');
            select.className = 'form-select';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'انتخاب کنید';
            select.appendChild(placeholder);
            const key = item.type === 'page' ? 'pages' : (item.type === 'post' ? 'posts' : 'courses');
            let found = !item.meta.slug;
            (linkSources[key] || []).forEach((row) => {
                const option = document.createElement('option');
                option.value = row.slug;
                option.textContent = row.title;
                if (row.slug === item.meta.slug) {
                    option.selected = true;
                    found = true;
                }
                select.appendChild(option);
            });
            if (item.meta.slug && !found) {
                const option = document.createElement('option');
                option.value = item.meta.slug;
                option.textContent = item.meta.slug;
                option.selected = true;
                select.appendChild(option);
            }
            select.addEventListener('change', () => { item.meta.slug = select.value; });
            destination.appendChild(select);
            return;
        }
        const input = document.createElement('input');
        input.className = 'form-control';
        input.dir = 'ltr';
        input.placeholder = '/path یا https://';
        input.value = item.url || '';
        input.addEventListener('input', () => { item.url = input.value; });
        destination.appendChild(input);
    }

    type.addEventListener('change', () => {
        item.type = type.value;
        paintDestination();
    });
    paintDestination();

    fields.append(label, type, destination, target);

    const actions = document.createElement('div');
    actions.className = 'menu-node-actions';
    actions.append(
        actionButton('زیرمنو', 'btn-outline-primary', () => {
            item.children.push(blankItem());
            renderTree();
        }),
        actionButton('بالا', 'btn-outline-secondary', () => moveSibling(siblings, index, -1)),
        actionButton('پایین', 'btn-outline-secondary', () => moveSibling(siblings, index, 1)),
        actionButton('حذف', 'btn-outline-danger', () => {
            siblings.splice(index, 1);
            renderTree();
        }),
    );

    body.append(fields, actions);
    head.append(handle, body);

    const childrenEl = document.createElement('div');
    childrenEl.className = 'menu-node-children';
    item.children.forEach((child, childIndex) => {
        childrenEl.appendChild(renderItem(child, item.children, childIndex));
    });

    node.append(head, childrenEl);
    bindDrag(node, item, siblings);
    return node;
}

function actionButton(text, style, onClick) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm ' + style;
    button.textContent = text;
    button.addEventListener('click', onClick);
    return button;
}

function moveSibling(list, index, direction) {
    const next = index + direction;
    if (next < 0 || next >= list.length) {
        return;
    }
    const [item] = list.splice(index, 1);
    list.splice(next, 0, item);
    renderTree();
}

function bindDrag(node, item, list) {
    node.addEventListener('dragstart', (event) => {
        dragItem = item;
        node.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', 'menu-item');
        event.stopPropagation();
    });
    node.addEventListener('dragend', () => {
        node.draggable = false;
        node.classList.remove('is-dragging');
        clearDropClasses();
        dragItem = null;
    });
    node.addEventListener('dragover', (event) => {
        if (!dragItem || dragItem === item || containsItem(dragItem, item)) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        const rect = node.getBoundingClientRect();
        const ratio = (event.clientY - rect.top) / rect.height;
        clearDropClasses();
        if (ratio < 0.28) {
            node.classList.add('is-drop-before');
            node.dataset.drop = 'before';
        } else if (ratio > 0.72) {
            node.classList.add('is-drop-after');
            node.dataset.drop = 'after';
        } else {
            node.classList.add('is-drop-child');
            node.dataset.drop = 'child';
        }
    });
    node.addEventListener('drop', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const position = node.dataset.drop || 'child';
        clearDropClasses();
        moveItem(item, list, position);
    });
}

function collectErrors(nodes, depth = 1) {
    const errors = [];
    if (depth > 20) {
        errors.push('عمق منو بیشتر از ۲۰ سطح است.');
        return errors;
    }
    nodes.forEach((node) => {
        if (!String(node.label || '').trim()) {
            errors.push('برچسب همه آیتم‌ها الزامی است.');
        }
        if (node.type === 'custom' && !String(node.url || '').trim()) {
            errors.push('آدرس لینک سفارشی الزامی است.');
        }
        if (node.type === 'route' && !String(node.route_name || '').trim()) {
            errors.push('نام مسیر الزامی است.');
        }
        if (['page', 'post', 'course'].includes(node.type) && !String(node.meta.slug || '').trim()) {
            errors.push('مقصد برگه، نوشته یا دوره را انتخاب کنید.');
        }
        errors.push(...collectErrors(node.children || [], depth + 1));
    });
    return [...new Set(errors)];
}

document.getElementById('add-menu-item').addEventListener('click', () => {
    tree.push(blankItem());
    renderTree();
});

document.getElementById('save-menu-tree').addEventListener('click', async () => {
    const errors = collectErrors(tree);
    if (errors.length) {
        statusEl.className = 'small text-danger';
        statusEl.textContent = errors.join(' ');
        return;
    }
    statusEl.className = 'small text-muted';
    statusEl.textContent = 'در حال ذخیره...';
    const response = await fetch(saveUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ tree }),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const messages = data.errors ? Object.values(data.errors).flat() : [data.message || 'ذخیره منو ناموفق بود.'];
        statusEl.className = 'small text-danger';
        statusEl.textContent = messages.join(' ');
        return;
    }
    if (Array.isArray(data.tree)) {
        tree = data.tree.map(normalizeNode);
        renderTree();
    }
    statusEl.className = 'small text-success';
    statusEl.textContent = data.message || 'ساختار منو ذخیره شد.';
});

renderTree();
</script>
@endsection
@endif
