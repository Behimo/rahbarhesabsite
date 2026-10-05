@extends('layouts.admin')

@php
    $mode = old('target_mode', $popup->target_mode ?: \App\Models\CmsPopup::MODE_PAGES);
    $selectedPages = old('pages', $popup->pages ?? []);
    $ruleMatch = old('rule_match', $popup->rules['match'] ?? 'all');
    $rulePath = old('rule_path', $popup->rules['path'] ?? '');
@endphp

@section('title', $popup->exists ? 'ویرایش پاپ‌آپ' : 'پاپ‌آپ جدید')

@section('heading', $popup->exists ? 'ویرایش: '.$popup->name : 'پاپ‌آپ جدید')

@section('content')
<form method="POST" action="{{ $popup->exists ? route('admin.popups.update', $popup) : route('admin.popups.store') }}">
    @csrf
    @if ($popup->exists) @method('PUT') @endif

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">محتوا</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="popup-name">نام داخلی *</label>
                    <input type="text" name="name" id="popup-name" value="{{ old('name', $popup->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    <div class="form-text">فقط در پنل دیده می‌شود.</div>
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

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">کجا نمایش داده شود</h5></div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-3 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="target_mode" id="mode-pages" value="pages" @checked($mode === 'pages')>
                    <label class="form-check-label" for="mode-pages">صفحات انتخاب‌شده</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="target_mode" id="mode-rules" value="rules" @checked($mode === 'rules')>
                    <label class="form-check-label" for="mode-rules">شرایط نمایش</label>
                </div>
            </div>
            @error('target_mode') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

            <div id="popup-pages" @if ($mode !== 'pages') hidden @endif>
                <p class="text-muted small">پاپ‌آپ فقط در صفحاتی که تیک می‌زنید بالا می‌آید.</p>
                @error('pages') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                <div class="row g-3">
                    @foreach ($pageGroups as $group => $pages)
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-medium mb-2">{{ $group }}</div>
                                @foreach ($pages as $key => $label)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="pages[]" value="{{ $key }}" id="page-{{ md5($key) }}" @checked(in_array($key, $selectedPages, true))>
                                        <label class="form-check-label" for="page-{{ md5($key) }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div id="popup-rules" @if ($mode !== 'rules') hidden @endif>
                <p class="text-muted small">به‌جای انتخاب صفحه، قانون مسیر را مشخص کنید. مثلاً همه آدرس‌هایی که با /blog شروع می‌شوند.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="rule-match">نوع شرط</label>
                        <select name="rule_match" id="rule-match" class="form-select @error('rule_match') is-invalid @enderror">
                            @foreach (\App\Models\CmsPopup::RULE_MATCHES as $value => $label)
                                <option value="{{ $value }}" @selected($ruleMatch === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('rule_match') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6" id="rule-path-wrap">
                        <label class="form-label" for="rule-path">مسیر</label>
                        <input type="text" name="rule_path" id="rule-path" value="{{ $rulePath }}" class="form-control @error('rule_path') is-invalid @enderror" dir="ltr" placeholder="/blog">
                        @error('rule_path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">شرایط تکمیلی</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="popup-audience">مخاطب</label>
                    <select name="audience" id="popup-audience" class="form-select">
                        @foreach (\App\Models\CmsPopup::AUDIENCES as $value => $label)
                            <option value="{{ $value }}" @selected(old('audience', $popup->audience) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="popup-frequency">تکرار نمایش</label>
                    <select name="frequency" id="popup-frequency" class="form-select">
                        @foreach (\App\Models\CmsPopup::FREQUENCIES as $value => $label)
                            <option value="{{ $value }}" @selected(old('frequency', $popup->frequency) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
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
                <div class="col-md-4">
                    <label class="form-label" for="popup-order">اولویت</label>
                    <input type="number" name="sort_order" id="popup-order" min="0" max="9999" value="{{ old('sort_order', $popup->sort_order ?? 0) }}" class="form-control">
                    <div class="form-text">اگر چند پاپ‌آپ همزمان منطبق باشند، عدد کوچک‌تر نمایش داده می‌شود.</div>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="popup-active" @checked(filter_var(old('is_active', $popup->is_active), FILTER_VALIDATE_BOOLEAN))>
                        <label class="form-check-label" for="popup-active">فعال</label>
                    </div>
                </div>
            </div>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">ذخیره</button>
            </div>
        </div>
    </div>
</form>

<script>
document.querySelectorAll('[name="target_mode"]').forEach((input) => {
    input.addEventListener('change', syncPopupTarget);
});

const ruleMatch = document.getElementById('rule-match');
if (ruleMatch) {
    ruleMatch.addEventListener('change', syncRulePath);
}

function syncPopupTarget() {
    const mode = document.querySelector('[name="target_mode"]:checked')?.value;
    document.getElementById('popup-pages').hidden = mode !== 'pages';
    document.getElementById('popup-rules').hidden = mode !== 'rules';
}

function syncRulePath() {
    const hide = ruleMatch && ruleMatch.value === 'all';
    document.getElementById('rule-path-wrap').hidden = hide;
}

syncPopupTarget();
syncRulePath();
</script>
@endsection
