@extends('layouts.admin')

@section('title', 'تنظیمات')

@section('heading', 'تنظیمات سایت')

@section('lede', 'اطلاعات تماس، شبکه‌های اجتماعی و برند. ذخیره کنار همین فرم می‌ماند.')

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
            <div class="card">
                <div class="card-header"><h5 class="mb-0">برند</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="settings-logo">لوگو</label>
                            <input id="settings-logo" type="text" name="site_logo" value="{{ old('site_logo', $settings['site_logo']) }}" class="form-control" dir="ltr">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="settings-favicon">فاوآیکون</label>
                            <input id="settings-favicon" type="text" name="site_favicon" value="{{ old('site_favicon', $settings['site_favicon']) }}" class="form-control" dir="ltr">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="settings-og">تصویر اشتراک‌گذاری</label>
                            <input id="settings-og" type="text" name="site_og_image" value="{{ old('site_og_image', $settings['site_og_image']) }}" class="form-control" dir="ltr">
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
@endsection
