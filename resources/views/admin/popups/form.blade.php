@extends('layouts.admin')

@php
    $mode = old('target_mode', $popup->target_mode ?: \App\Models\CmsPopup::MODE_PAGES);
    $selectedPages = old('pages', $popup->pages ?? []);
    $ruleMatch = old('rule_match', $popup->rules['match'] ?? 'all');
    $rulePath = old('rule_path', $popup->rules['path'] ?? '');
    $audience = old('audience', $popup->audience ?: 'all');
    $frequency = old('frequency', $popup->frequency ?: 'session');
@endphp

@section('title', $popup->exists ? 'ویرایش پاپ‌آپ' : 'پاپ‌آپ جدید')

@section('heading', $popup->exists ? 'ویرایش: '.$popup->name : 'پاپ‌آپ جدید')

@section('lede', 'متن پنجره، صفحه‌هایی که در آن‌ها باز می‌شود، و اینکه هر بازدیدکننده چند بار آن را ببیند.')

@section('vendor-style')
@include('admin.partials.composer-styles')
@endsection

@section('content')
<form method="POST" action="{{ $popup->exists ? route('admin.popups.update', $popup) : route('admin.popups.store') }}">
    @csrf
    @if ($popup->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">متن پنجره</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="popup-name">نام داخلی *</label>
                            <input type="text" name="name" id="popup-name" value="{{ old('name', $popup->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                            <div class="form-text">فقط در فهرست پنل دیده می‌شود.</div>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="popup-title">عنوان *</label>
                            <input type="text" name="title" id="popup-title" value="{{ old('title', $popup->title) }}" class="form-control @error('title') is-invalid @enderror" required>
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="popup-body">متن *</label>
                            <textarea name="body" id="popup-body" rows="4" class="form-control @error('body') is-invalid @enderror" required>{{ old('body', $popup->body) }}</textarea>
                            @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="popup-image">آدرس تصویر</label>
                            <input type="text" name="image_url" id="popup-image" value="{{ old('image_url', $popup->image_url) }}" class="form-control @error('image_url') is-invalid @enderror" dir="ltr" placeholder="/site/images/banner.webp">
                            @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="popup-button-label">متن دکمه</label>
                            <input type="text" name="button_label" id="popup-button-label" value="{{ old('button_label', $popup->button_label) }}" class="form-control @error('button_label') is-invalid @enderror">
                            @error('button_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="popup-button-url">لینک دکمه</label>
                            <input type="text" name="button_url" id="popup-button-url" value="{{ old('button_url', $popup->button_url) }}" class="form-control @error('button_url') is-invalid @enderror" dir="ltr" placeholder="/courses">
                            @error('button_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">زمان و تکرار</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-label">مخاطب</div>
                        <div class="sb-pills">
                            @foreach (\App\Models\CmsPopup::AUDIENCES as $value => $label)
                                <label class="sb-pill">
                                    <input type="radio" name="audience" value="{{ $value }}" @checked($audience === $value)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="form-label">تکرار نمایش</div>
                        <div class="sb-pills sb-pills--2">
                            @foreach (\App\Models\CmsPopup::FREQUENCIES as $value => $label)
                                <label class="sb-pill">
                                    <input type="radio" name="frequency" value="{{ $value }}" @checked($frequency === $value)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="popup-delay">تأخیر (ثانیه)</label>
                            <input type="number" name="delay_seconds" id="popup-delay" min="0" max="300" value="{{ old('delay_seconds', $popup->delay_seconds ?? 0) }}" class="form-control @error('delay_seconds') is-invalid @enderror">
                            @error('delay_seconds') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="popup-start">شروع نمایش</label>
                            <input type="datetime-local" name="starts_at" id="popup-start" value="{{ old('starts_at', optional($popup->starts_at)->format('Y-m-d\TH:i')) }}" class="form-control @error('starts_at') is-invalid @enderror">
                            @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="popup-end">پایان نمایش</label>
                            <input type="datetime-local" name="ends_at" id="popup-end" value="{{ old('ends_at', optional($popup->ends_at)->format('Y-m-d\TH:i')) }}" class="form-control @error('ends_at') is-invalid @enderror">
                            @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 admin-form-side">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">انتشار</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="popup-active" @checked(filter_var(old('is_active', $popup->is_active), FILTER_VALIDATE_BOOLEAN))>
                        <label class="form-check-label" for="popup-active">نمایش در سایت</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="popup-order">ترتیب</label>
                        <input type="number" name="sort_order" id="popup-order" min="0" max="9999" value="{{ old('sort_order', $popup->sort_order ?? 0) }}" class="form-control">
                        <div class="form-text">عدد کوچک‌تر زودتر بررسی می‌شود.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="popup-priority">اولویت</label>
                        <input type="number" name="priority" id="popup-priority" min="0" max="9999" value="{{ old('priority', $popup->priority ?? 0) }}" class="form-control">
                        <div class="form-text">اگر چند پاپ‌آپ همزمان باشند، عدد بزرگ‌تر نمایش داده می‌شود.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">ذخیره</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <h5 class="mb-0">جای نمایش</h5>
                    <span class="sb-count" id="popup-page-count" @if ($mode !== 'pages') hidden @endif></span>
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

                    <div id="popup-pages" @if ($mode !== 'pages') hidden @endif>
                        @error('pages') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                        <input type="search" id="popup-page-filter" class="form-control form-control-sm mb-3" placeholder="جستجوی صفحه" autocomplete="off">
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
                            <p class="sb-page-empty" id="popup-page-empty" hidden>صفحه‌ای با این نام نیست.</p>
                        </div>
                    </div>

                    <div id="popup-rules" @if ($mode !== 'rules') hidden @endif>
                        <p class="text-muted small">مثلاً همه آدرس‌هایی که با /blog شروع می‌شوند.</p>
                        <div class="mb-3">
                            <label class="form-label" for="rule-match">نوع شرط</label>
                            <select name="rule_match" id="rule-match" class="form-select @error('rule_match') is-invalid @enderror">
                                @foreach (\App\Models\CmsPopup::RULE_MATCHES as $value => $label)
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
    input.addEventListener('change', syncPopupTarget);
});
document.querySelectorAll('#popup-pages input[type="checkbox"]').forEach((input) => {
    input.addEventListener('change', syncPopupCount);
});

const ruleMatch = document.getElementById('rule-match');
const pageFilter = document.getElementById('popup-page-filter');
if (ruleMatch) ruleMatch.addEventListener('change', syncRulePath);
if (pageFilter) pageFilter.addEventListener('input', filterPopupPages);

function syncPopupTarget() {
    const mode = document.querySelector('[name="target_mode"]:checked')?.value;
    document.getElementById('popup-pages').hidden = mode !== 'pages';
    document.getElementById('popup-rules').hidden = mode !== 'rules';
    document.getElementById('popup-page-count').hidden = mode !== 'pages';
}

function syncRulePath() {
    const hide = ruleMatch && ruleMatch.value === 'all';
    document.getElementById('rule-path-wrap').hidden = hide;
}

function syncPopupCount() {
    const count = document.querySelectorAll('#popup-pages input[type="checkbox"]:checked').length;
    const node = document.getElementById('popup-page-count');
    if (node) node.textContent = count === 0 ? 'هیچ صفحه‌ای' : count + ' صفحه';
}

function filterPopupPages() {
    const query = (pageFilter?.value || '').trim();
    let visible = 0;
    document.querySelectorAll('#popup-pages [data-page-group]').forEach((group) => {
        let groupVisible = 0;
        group.querySelectorAll('[data-page-label]').forEach((item) => {
            const match = query === '' || item.dataset.pageLabel.includes(query);
            item.hidden = !match;
            if (match) groupVisible += 1;
        });
        group.hidden = groupVisible === 0;
        visible += groupVisible;
    });
    document.getElementById('popup-page-empty').hidden = visible !== 0;
}

syncPopupTarget();
syncRulePath();
syncPopupCount();
</script>
@endsection
