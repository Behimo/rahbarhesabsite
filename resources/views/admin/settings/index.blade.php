@extends('layouts.admin')

@section('title', 'تنظیمات')

@section('heading', 'تنظیمات سایت')

@section('lede', 'اطلاعات تماس، شبکه‌های اجتماعی، برند و نوار بالای هدر. ذخیره کنار همین فرم می‌ماند.')

@section('vendor-style')
@include('admin.partials.media-picker-styles')
@endsection

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">اطلاعات تماس</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="settings-email">ایمیل</label>
                            <input id="settings-email" type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}" class="form-control" required dir="ltr">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="settings-phone">تلفن</label>
                            <input id="settings-phone" type="text" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone']) }}" class="form-control" dir="ltr">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">شبکه‌های اجتماعی</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="settings-linkedin">لینکدین</label>
                            <input id="settings-linkedin" type="url" name="social_linkedin" value="{{ old('social_linkedin', $settings['social_linkedin']) }}" class="form-control" dir="ltr">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="settings-telegram">تلگرام</label>
                            <input id="settings-telegram" type="url" name="social_telegram" value="{{ old('social_telegram', $settings['social_telegram']) }}" class="form-control" dir="ltr">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="settings-instagram">اینستاگرام</label>
                            <input id="settings-instagram" type="url" name="social_instagram" value="{{ old('social_instagram', $settings['social_instagram']) }}" class="form-control" dir="ltr">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">برند</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="settings-logo">لوگو</label>
                            <div class="input-group">
                                <input id="settings-logo" type="text" name="site_logo" value="{{ old('site_logo', $settings['site_logo']) }}" class="form-control" dir="ltr">
                                <button type="button" class="btn btn-outline-primary" data-media-target="#settings-logo">انتخاب</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="settings-favicon">فاوآیکون</label>
                            <div class="input-group">
                                <input id="settings-favicon" type="text" name="site_favicon" value="{{ old('site_favicon', $settings['site_favicon']) }}" class="form-control" dir="ltr">
                                <button type="button" class="btn btn-outline-primary" data-media-target="#settings-favicon">انتخاب</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="settings-og">تصویر اشتراک‌گذاری</label>
                            <div class="input-group">
                                <input id="settings-og" type="text" name="site_og_image" value="{{ old('site_og_image', $settings['site_og_image']) }}" class="form-control" dir="ltr">
                                <button type="button" class="btn btn-outline-primary" data-media-target="#settings-og">انتخاب</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @php
                $topBarMode = old('top_bar_mode', $settings['top_bar_mode'] ?? 'text');
                $topBarImage = old('top_bar_image', $settings['top_bar_image'] ?? '');
            @endphp
            <div class="card">
                <div class="card-header"><h5 class="mb-0">نوار بالای هدر</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="settings-top-bar-enabled"
                                    name="top_bar_enabled"
                                    value="1"
                                    @checked(old('top_bar_enabled', $settings['top_bar_enabled']))
                                >
                                <label class="form-check-label" for="settings-top-bar-enabled">نمایش نوار بالای هدر</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-block mb-2">نوع نمایش</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="top_bar_mode"
                                        id="settings-top-bar-mode-text"
                                        value="text"
                                        @checked($topBarMode !== 'image')
                                    >
                                    <label class="form-check-label" for="settings-top-bar-mode-text">متن و دکمه</label>
                                </div>
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="top_bar_mode"
                                        id="settings-top-bar-mode-image"
                                        value="image"
                                        @checked($topBarMode === 'image')
                                    >
                                    <label class="form-check-label" for="settings-top-bar-mode-image">تصویر یا گیف</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12" data-top-bar-panel="text" @style(['display: none' => $topBarMode === 'image'])>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="settings-top-bar-text">متن نوار</label>
                                    <input
                                        id="settings-top-bar-text"
                                        type="text"
                                        name="top_bar_text"
                                        value="{{ old('top_bar_text', $settings['top_bar_text']) }}"
                                        class="form-control"
                                        placeholder="مثلاً: دوره ویژه حسابداری با تخفیف محدود"
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="settings-top-bar-button-text">متن دکمه</label>
                                    <input
                                        id="settings-top-bar-button-text"
                                        type="text"
                                        name="top_bar_button_text"
                                        value="{{ old('top_bar_button_text', $settings['top_bar_button_text']) }}"
                                        class="form-control"
                                        placeholder="مثلاً: ثبت‌نام"
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="settings-top-bar-button-url">لینک دکمه</label>
                                    <input
                                        id="settings-top-bar-button-url"
                                        type="text"
                                        name="top_bar_button_url"
                                        value="{{ old('top_bar_button_url', $settings['top_bar_button_url']) }}"
                                        class="form-control"
                                        dir="ltr"
                                        placeholder="/courses یا https://..."
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="col-12" data-top-bar-panel="image" @style(['display: none' => $topBarMode !== 'image'])>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="settings-top-bar-image">تصویر یا گیف</label>
                                    <div class="input-group">
                                        <input
                                            id="settings-top-bar-image"
                                            type="text"
                                            name="top_bar_image"
                                            value="{{ $topBarImage }}"
                                            class="form-control"
                                            dir="ltr"
                                            placeholder="آدرس تصویر یا گیف"
                                        >
                                        <button type="button" class="btn btn-outline-primary" data-media-target="#settings-top-bar-image">انتخاب</button>
                                    </div>
                                    <div class="form-text">فرمت‌های JPG، PNG، WEBP و GIF پشتیبانی می‌شوند.</div>
                                    @if ($topBarImage)
                                        <div class="mt-2">
                                            <img src="{{ $topBarImage }}" alt="پیش‌نمایش نوار بالا" style="max-width: 100%; max-height: 72px; object-fit: contain; border-radius: 8px;">
                                        </div>
                                    @endif
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="settings-top-bar-image-url">لینک تصویر / گیف</label>
                                    <input
                                        id="settings-top-bar-image-url"
                                        type="text"
                                        name="top_bar_image_url"
                                        value="{{ old('top_bar_image_url', $settings['top_bar_image_url'] ?? '') }}"
                                        class="form-control"
                                        dir="ltr"
                                        placeholder="/courses یا https://..."
                                    >
                                    <div class="form-text">با کلیک روی تصویر، کاربر به این آدرس می‌رود.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 admin-form-side">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">ذخیره</h5></div>
                <div class="card-body">
                    <p class="text-muted small">تغییرها بعد از ذخیره روی سایت عمومی دیده می‌شود.</p>
                    @if (admin_can('settings', 'update'))
                        <button type="submit" class="btn btn-primary w-100">ذخیره تنظیمات</button>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-1">درگاه‌های پرداخت</h5>
                    <p class="text-muted mb-3">
                        @foreach ($gateways as $gateway)
                            {{ $gateway['label'] }}@if (! $loop->last)، @endif
                        @endforeach
                    </p>
                    <a href="{{ route('admin.gateways.index') }}" class="btn btn-outline-primary w-100">مدیریت درگاه‌ها</a>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="mb-1">درایورهای پیامک</h5>
                    <p class="text-muted mb-3">فعال‌سازی سرویس پیامک، انتخاب درایور اصلی و تنظیم کلید API و قالب کد ورود.</p>
                    <a href="{{ route('admin.sms.index') }}" class="btn btn-outline-primary w-100">مدیریت پیامک</a>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="card mt-4">
    <div class="card-body">
        <h5 class="mb-3">درون‌ریزی و برون‌بری</h5>
        @if (admin_can('transfer', 'view'))
            <a href="{{ route('admin.export') }}" class="btn btn-outline-secondary mb-3">دانلود JSON</a>
        @endif
        @if (admin_can('transfer', 'create'))
            <form method="POST" action="{{ route('admin.import') }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-md-8"><input type="file" name="export_file" class="form-control" accept=".json"></div>
                <div class="col-md-4"><button class="btn btn-outline-primary w-100">درون‌ریزی</button></div>
            </form>
        @endif
    </div>
</div>

@include('admin.partials.media-picker-modal')
@endsection

@section('page-script')
@include('admin.partials.composer-scripts')
<script>
    (() => {
        const radios = document.querySelectorAll('input[name="top_bar_mode"]');
        const panels = document.querySelectorAll('[data-top-bar-panel]');

        const syncTopBarPanels = () => {
            const mode = document.querySelector('input[name="top_bar_mode"]:checked')?.value || 'text';
            panels.forEach((panel) => {
                panel.style.display = panel.getAttribute('data-top-bar-panel') === mode ? '' : 'none';
            });
        };

        radios.forEach((radio) => radio.addEventListener('change', syncTopBarPanels));
        syncTopBarPanels();
    })();
</script>
@endsection
