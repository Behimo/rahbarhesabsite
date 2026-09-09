@extends('layouts.admin')
@section('title', $menu->exists ? 'ویرایش منو' : 'منوی جدید')
@section('content')
<h4 class="mb-4">{{ $menu->exists ? 'ویرایش منو' : 'منوی جدید' }}</h4>
<form method="POST" action="{{ $menu->exists ? route('admin.menus.update', $menu) : route('admin.menus.store') }}" class="card mb-4">
    @csrf @if($menu->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label">نام</label><input name="name" class="form-control" value="{{ old('name', $menu->name) }}" required></div>
        <div class="col-md-4"><label class="form-label">Slug</label><input name="slug" class="form-control" dir="ltr" value="{{ old('slug', $menu->slug) }}" required></div>
        <div class="col-md-4"><label class="form-label">محل نمایش</label><input name="location" class="form-control" value="{{ old('location', $menu->location) }}" placeholder="primary"></div>
        <div class="col-12"><button class="btn btn-primary">ذخیره</button></div>
    </div>
</form>

@if($menu->exists)
<div class="card" id="menu-builder" data-save-url="{{ route('admin.menus.tree', $menu) }}">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">آیتم‌های منو</h5>
        <button type="button" class="btn btn-sm btn-primary" id="add-menu-item">افزودن آیتم</button>
    </div>
    <div class="card-body">
        <div id="menu-tree" class="list-group"></div>
        <button type="button" class="btn btn-success mt-3" id="save-menu-tree">ذخیره ساختار منو</button>
    </div>
</div>
<script>
const initialTree = @json($tree ?? []);
const treeEl = document.getElementById('menu-tree');
let tree = JSON.parse(JSON.stringify(initialTree));

function renderTree() {
    treeEl.innerHTML = tree.map((item, i) => `
        <div class="list-group-item">
            <input class="form-control mb-2" data-i="${i}" data-f="label" value="${item.label || ''}" placeholder="برچسب">
            <select class="form-select mb-2" data-i="${i}" data-f="type">
                <option value="custom" ${item.type==='custom'?'selected':''}>لینک سفارشی</option>
                <option value="route" ${item.type==='route'?'selected':''}>Route</option>
            </select>
            <input class="form-control mb-2" data-i="${i}" data-f="url" value="${item.url || ''}" placeholder="URL یا route name">
            <button type="button" class="btn btn-sm btn-danger" data-remove="${i}">حذف</button>
        </div>`).join('');
}

treeEl.addEventListener('input', e => {
    const i = e.target.dataset.i, f = e.target.dataset.f;
    if (i !== undefined && f) tree[i][f] = e.target.value;
});
treeEl.addEventListener('click', e => {
    if (e.target.dataset.remove) { tree.splice(+e.target.dataset.remove, 1); renderTree(); }
});
document.getElementById('add-menu-item').onclick = () => { tree.push({label:'', type:'custom', url:'#'}); renderTree(); };
document.getElementById('save-menu-tree').onclick = async () => {
    await fetch(document.getElementById('menu-builder').dataset.saveUrl, {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({tree})
    });
    alert('منو ذخیره شد');
};
renderTree();
</script>
@endif
@endsection
