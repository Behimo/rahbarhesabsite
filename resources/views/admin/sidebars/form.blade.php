@extends('layouts.admin')

@php
    $mode = old('target_mode', $sidebar->target_mode ?: \App\Models\CmsSidebar::MODE_PAGES);
    $selectedPages = old('pages', $sidebar->pages ?? []);
    $ruleMatch = old('rule_match', $sidebar->rules['match'] ?? 'all');
    $rulePath = old('rule_path', $sidebar->rules['path'] ?? '');
    $source = old('source', $sidebar->source ?: \App\Models\CmsSidebar::SOURCE_COURSE);
    $selection = old('selection', $sidebar->selection ?: \App\Models\CmsSidebar::SELECTION_LATEST);
    $categoryId = (string) old('category_id', $sidebar->category_id ?? '');
    $sourceHints = [
        'product' => 'کالاهای فروشگاه به‌جز دوره',
        'course' => 'دوره‌های منتشرشده',
        'post' => 'مطالب منتشرشده بلاگ',
    ];
@endphp

@section('title', $sidebar->exists ? 'ویرایش سایدبار' : 'سایدبار جدید')

@section('heading', $sidebar->exists ? 'ویرایش: '.$sidebar->name : 'سایدبار جدید')

@section('lede', 'فهرست کنار صفحه را بسازید: چه چیزی نشان داده شود و در کدام صفحه‌ها.')

@section('vendor-style')
<style>
    .sb-choice, .sb-pill, .sb-page, .sb-segment label { margin: 0; cursor: pointer; }
    .sb-choice input, .sb-pill input, .sb-page input, .sb-segment input { position: absolute; opacity: 0; pointer-events: none; }
    .sb-choice { position: relative; display: flex; flex-direction: column; gap: .2rem; height: 100%; padding: .9rem 1rem; border: 1px solid var(--bs-border-color); border-radius: .75rem; background: var(--bs-paper-bg, #fff); }
    .sb-choice:has(input:checked) { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), .08); }
    .sb-choice:focus-within, .sb-pill:focus-within, .sb-page:focus-within, .sb-segment label:focus-within { outline: 2px solid var(--bs-primary); outline-offset: 2px; }
    .sb-choice__title, .sb-pill span, .sb-segment span { font-weight: 600; }
    .sb-choice__hint { color: var(--bs-secondary-color); font-size: .8125rem; line-height: 1.55; }
    .sb-pills { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
    .sb-pill, .sb-page, .sb-segment label { position: relative; }
    .sb-pill span, .sb-page span, .sb-segment span { display: flex; align-items: center; justify-content: center; min-height: 2.5rem; padding: .35rem .75rem; border: 1px solid var(--bs-border-color); border-radius: .65rem; background: var(--bs-paper-bg, #fff); text-align: center; line-height: 1.4; }
    .sb-pill:has(input:checked) span, .sb-page:has(input:checked) span { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), .1); color: var(--bs-primary); }
    .sb-page span { justify-content: flex-start; min-height: 2.15rem; border-radius: 999px; font-size: .875rem; font-weight: 500; }
    .sb-segment { display: grid; grid-template-columns: 1fr 1fr; gap: .35rem; margin-bottom: 1rem; padding: .25rem; border-radius: .8rem; background: rgba(var(--bs-secondary-rgb), .1); }
    .sb-segment span { border-color: transparent; background: transparent; }
    .sb-segment label:has(input:checked) span { border-color: var(--bs-border-color); background: var(--bs-paper-bg, #fff); color: var(--bs-primary); }
    .sb-pages-scroll { max-height: 28rem; overflow: auto; padding-inline-end: .15rem; }
    .sb-page-group + .sb-page-group { margin-top: .9rem; }
    .sb-page-group__title { margin-bottom: .45rem; color: var(--bs-secondary-color); font-size: .75rem; font-weight: 700; }
    .sb-page-grid { display: flex; flex-wrap: wrap; gap: .4rem; }
    .sb-count { color: var(--bs-primary); font-size: .8125rem; font-weight: 600; white-space: nowrap; }
    .sb-summary { margin: 0 0 1rem; color: var(--bs-secondary-color); font-size: .875rem; line-height: 1.6; }
    .sb-page-empty { margin: .75rem 0 0; color: var(--bs-secondary-color); font-size: .875rem; }
    @media (max-width: 575.98px) { .sb-pills { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<form method="POST" class="sb-form" action="{{ $sidebar->exists ? route('admin.sidebars.update', $sidebar) : route('admin.sidebars.store') }}">
    @csrf
    @if ($sidebar->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h5>مشخصات</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="sidebar-name">نام داخلی *</label>
                            <input type="text" name="name" id="sidebar-name" value="{{ old('name', $sidebar->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                            <div class="form-text">فقط در فهرست پنل دیده می‌شود.</div>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sidebar-title">عنوان روی سایت *</label>
                            <input type="text" name="title" id="sidebar-title" value="{{ old('title', $sidebar->title) }}" class="form-control @error('title') is-invalid @enderror" required>
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sidebar-limit">تعداد آیتم *</label>
                            <input type="number" name="limit" id="sidebar-limit" min="1" max="12" value="{{ old('limit', $sidebar->limit ?? 5) }}" class="form-control @error('limit') is-invalid @enderror" required>
                            @error('limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sidebar-order">ترتیب در صفحه</label>
                            <input type="number" name="sort_order" id="sidebar-order" min="0" max="9999" value="{{ old('sort_order', $sidebar->sort_order ?? 0) }}" class="form-control">
                            <div class="form-text">اگر چند سایدبار روی یک صفحه باشد، عدد کوچک‌تر بالاتر است.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5>محتوای فهرست</h5></div>
                <div class="card-body">
                    @error('source') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                    <div class="row g-2 mb-4">
                        @foreach (\App\Models\CmsSidebar::SOURCES as $value => $label)
                            <div class="col-md-4">
                                <label class="sb-choice" for="source-{{ $value }}">
                                    <input type="radio" name="source" id="source-{{ $value }}" value="{{ $value }}" data-label="{{ $label }}" @checked($source === $value)>
                                    <span class="sb-choice__title">{{ $label }}</span>
                                    <span class="sb-choice__hint">{{ $sourceHints[$value] }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="mb-0">
                        <div class="form-label">چطور انتخاب شود *</div>
                        @error('selection') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                        <div class="sb-pills" id="sidebar-selection">
                            @foreach (\App\Models\CmsSidebar::SELECTIONS as $value => $label)
                                <label class="sb-pill">
                                    <input type="radio" name="selection" value="{{ $value }}" @checked($selection === $value) @required($loop->first)>
                                    <span @if ($value === 'bestseller') data-ranked @endif>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-3" id="sidebar-category-wrap" @if ($selection !== 'category') hidden @endif>
                        <label class="form-label" for="sidebar-category">دسته</label>
                        <select name="category_id" id="sidebar-category" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">انتخاب دسته</option>
                            @foreach ($productCategories as $category)
                                <option value="{{ $category->id }}" data-for="product course" @selected($categoryId === (string) $category->id)>
                                    {{ str_repeat('– ', $category->treeDepth) }}{{ $category->name }}
                                </option>
                            @endforeach
                            @foreach ($postCategories as $category)
                                <option value="{{ $category->id }}" data-for="post" @selected($categoryId === (string) $category->id)>
                                    {{ str_repeat('– ', $category->treeDepth) }}{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">زیردسته‌های همین شاخه هم در فهرست می‌آیند.</div>
                        @error('category_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 admin-form-side">
            <div class="card mb-4">
                <div class="card-header"><h5>انتشار</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="sidebar-active" @checked(filter_var(old('is_active', $sidebar->is_active), FILTER_VALIDATE_BOOLEAN))>
                        <label class="form-check-label" for="sidebar-active">نمایش در سایت</label>
                    </div>
                    <p class="sb-summary" id="sidebar-summary"></p>
                    <button type="submit" class="btn btn-primary w-100">ذخیره</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <h5>جای نمایش</h5>
                    <span class="sb-count" id="sidebar-page-count" @if ($mode !== 'pages') hidden @endif></span>
                </div>
                <div class="card-body">
                    @error('target_mode') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                    <div class="sb-segment">
                        <label>
                            <input type="radio" name="target_mode" id="mode-pages" value="pages" @checked($mode === 'pages')>
                            <span>صفحات</span>
                        </label>
                        <label>
                            <input type="radio" name="target_mode" id="mode-rules" value="rules" @checked($mode === 'rules')>
                            <span>مسیر</span>
                        </label>
                    </div>

                    <div id="sidebar-pages" @if ($mode !== 'pages') hidden @endif>
                        @error('pages') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                        <input type="search" id="sidebar-page-filter" class="form-control form-control-sm mb-3" placeholder="جستجوی صفحه" autocomplete="off">
                        <div class="sb-pages-scroll">
                            @foreach ($pageGroups as $group => $pages)
                                <div class="sb-page-group" data-page-group>
                                    <div class="sb-page-group__title">{{ $group }}</div>
                                    <div class="sb-page-grid">
                                        @foreach ($pages as $key => $label)
                                            <label class="sb-page" data-page-label="{{ $label }}">
                                                <input type="checkbox" name="pages[]" value="{{ $key }}" id="page-{{ md5($key) }}" @checked(in_array($key, $selectedPages, true))>
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                            <p class="sb-page-empty" id="sidebar-page-empty" hidden>صفحه‌ای با این نام نیست.</p>
                        </div>
                    </div>

                    <div id="sidebar-rules" @if ($mode !== 'rules') hidden @endif>
                        <p class="text-muted small">مثلاً همه آدرس‌هایی که با /blog شروع می‌شوند.</p>
                        <div class="mb-3">
                            <label class="form-label" for="rule-match">نوع شرط</label>
                            <select name="rule_match" id="rule-match" class="form-select @error('rule_match') is-invalid @enderror">
                                @foreach (\App\Models\CmsSidebar::RULE_MATCHES as $value => $label)
                                    <option value="{{ $value }}" @selected($ruleMatch === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('rule_match') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div id="rule-path-wrap">
                            <label class="form-label" for="rule-path">مسیر</label>
                            <input type="text" name="rule_path" id="rule-path" value="{{ $rulePath }}" class="form-control @error('rule_path') is-invalid @enderror" dir="ltr" placeholder="/blog">
                            @error('rule_path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.querySelectorAll('[name="target_mode"]').forEach((input) => {
    input.addEventListener('change', syncSidebarTarget);
});
document.querySelectorAll('[name="source"]').forEach((input) => {
    input.addEventListener('change', syncSidebarContent);
});
document.querySelectorAll('[name="selection"]').forEach((input) => {
    input.addEventListener('change', syncSidebarContent);
});
document.querySelectorAll('#sidebar-pages input[type="checkbox"]').forEach((input) => {
    input.addEventListener('change', syncPageCount);
});

const ruleMatch = document.getElementById('rule-match');
const pageFilter = document.getElementById('sidebar-page-filter');
const limitInput = document.getElementById('sidebar-limit');
if (ruleMatch) ruleMatch.addEventListener('change', syncRulePath);
if (pageFilter) pageFilter.addEventListener('input', filterPages);
if (limitInput) limitInput.addEventListener('input', syncSidebarContent);

function syncSidebarTarget() {
    const mode = document.querySelector('[name="target_mode"]:checked')?.value;
    document.getElementById('sidebar-pages').hidden = mode !== 'pages';
    document.getElementById('sidebar-rules').hidden = mode !== 'rules';
    document.getElementById('sidebar-page-count').hidden = mode !== 'pages';
}

function syncRulePath() {
    const hide = ruleMatch && ruleMatch.value === 'all';
    document.getElementById('rule-path-wrap').hidden = hide;
}

function currentSelection() {
    return document.querySelector('[name="selection"]:checked')?.value || 'latest';
}

function syncSidebarContent() {
    const sourceInput = document.querySelector('[name="source"]:checked');
    const source = sourceInput?.value || 'course';
    const categoryWrap = document.getElementById('sidebar-category-wrap');
    const category = document.getElementById('sidebar-category');
    const ranked = document.querySelector('[data-ranked]');
    const showCategory = currentSelection() === 'category';

    categoryWrap.hidden = !showCategory;
    if (category) category.disabled = !showCategory;

    if (ranked) {
        ranked.textContent = source === 'post' ? 'پربازدیدترین‌ها' : 'پرفروش‌ترین‌ها';
    }

    if (category) {
        let selectedVisible = false;
        category.querySelectorAll('option[data-for]').forEach((option) => {
            const visible = option.dataset.for.split(' ').includes(source);
            option.hidden = !visible;
            option.disabled = !visible;
            if (visible && option.value === category.value) selectedVisible = true;
        });
        if (category.value && !selectedVisible) category.value = '';
    }

    const selectionLabel = document.querySelector('[name="selection"]:checked')?.nextElementSibling?.textContent?.trim() || '';
    const sourceLabel = sourceInput?.dataset.label || '';
    const limit = limitInput?.value || '5';
    const summary = document.getElementById('sidebar-summary');
    if (summary) summary.textContent = sourceLabel + '، ' + selectionLabel + '، ' + limit + ' مورد';
}

function syncPageCount() {
    const count = document.querySelectorAll('#sidebar-pages input[type="checkbox"]:checked').length;
    const node = document.getElementById('sidebar-page-count');
    if (node) node.textContent = count === 0 ? 'هیچ صفحه‌ای' : count + ' صفحه';
}

function filterPages() {
    const query = (pageFilter?.value || '').trim();
    let visible = 0;
    document.querySelectorAll('[data-page-group]').forEach((group) => {
        let groupVisible = 0;
        group.querySelectorAll('[data-page-label]').forEach((item) => {
            const match = query === '' || item.dataset.pageLabel.includes(query);
            item.hidden = !match;
            if (match) groupVisible += 1;
        });
        group.hidden = groupVisible === 0;
        visible += groupVisible;
    });
    document.getElementById('sidebar-page-empty').hidden = visible !== 0;
}

syncSidebarTarget();
syncRulePath();
syncSidebarContent();
syncPageCount();
</script>
@endsection
