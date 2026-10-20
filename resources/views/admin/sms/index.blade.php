@extends('layouts.admin')

@section('title', 'درایورهای پیامک')
@section('lede', 'هر درایور را جداگانه فعال کنید و فقط یک درایور فعال را برای ارسال کد ورود انتخاب کنید.')

@section('content')
@php
    $editable = admin_can('settings', 'update');
    $selected = old('default', collect($drivers)->firstWhere('selected', true)['name'] ?? '');
@endphp
<div class="card">
    <div class="card-body">
        <p class="mb-2">بدون درایور اصلی فعال و کامل، ارسال کد ورود انجام نمی‌شود و درخواست OTP خطا می‌دهد. فعال‌کردن درایور به تنهایی باعث ارسال از آن نمی‌شود.</p>
        <p class="text-muted mb-0">کلید API در پایگاه داده به‌صورت رمزنگاری‌شده نگهداری می‌شود. فیلد کلید همیشه خالی است: خالی گذاشتن آن کلید قبلی را نگه می‌دارد.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.sms.update') }}" class="mt-4">
    @csrf
    @method('PUT')

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">درایور اصلی ارسال کد</h5>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="default" id="sms-default-none" value="" @checked(! $selected) @disabled(! $editable)>
                <label class="form-check-label" for="sms-default-none">هیچ‌کدام (ارسال OTP خاموش)</label>
            </div>
            @foreach ($drivers as $driver)
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="default" id="sms-default-{{ $driver['name'] }}" value="{{ $driver['name'] }}" @checked($selected === $driver['name']) @disabled(! $editable)>
                    <label class="form-check-label" for="sms-default-{{ $driver['name'] }}">{{ $driver['label'] }}</label>
                </div>
            @endforeach
            @error('default') <div class="text-danger" role="alert">{{ $message }}</div> @enderror
        </div>
    </div>

    @foreach ($drivers as $driver)
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="card-title mb-1">{{ $driver['label'] }}</h5>
                        <small class="text-muted" dir="ltr">{{ $driver['name'] }}</small>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="enabled[{{ $driver['name'] }}]" value="0" @disabled(! $editable)>
                        <input class="form-check-input" type="checkbox" role="switch" name="enabled[{{ $driver['name'] }}]" id="sms-enabled-{{ $driver['name'] }}" value="1" @checked(filter_var(old('enabled.'.$driver['name'], $driver['enabled']), FILTER_VALIDATE_BOOLEAN)) @disabled(! $editable)>
                        <label class="form-check-label" for="sms-enabled-{{ $driver['name'] }}">فعال</label>
                    </div>
                </div>

                @foreach ($driver['fields'] as $field)
                    @php($path = 'settings.'.$driver['name'].'.'.$field['key'])
                    <div class="mb-3">
                        <label class="form-label" for="sms-{{ $driver['name'] }}-{{ $field['key'] }}">{{ $field['label'] }}</label>
                        <input
                            class="form-control @error($path) is-invalid @enderror"
                            type="{{ $field['type'] === 'secret' ? 'password' : ($field['type'] === 'integer' ? 'number' : 'text') }}"
                            id="sms-{{ $driver['name'] }}-{{ $field['key'] }}"
                            name="settings[{{ $driver['name'] }}][{{ $field['key'] }}]"
                            value="{{ $field['type'] === 'secret' ? '' : old($path, $field['value']) }}"
                            @if ($field['type'] === 'secret') autocomplete="new-password" @else autocomplete="off" @endif
                            @if ($field['type'] === 'integer') min="1" max="2147483647" @endif
                            dir="ltr"
                            spellcheck="false"
                            @disabled(! $editable)
                        >
                        @error($path) <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
                        @if ($driver['name'] === 'smsir' && $field['key'] === 'code_parameter')
                            <small class="text-muted">اگر متن قالب شامل <code dir="ltr">#CODE#</code> است، اینجا فقط <code dir="ltr">CODE</code> بنویسید؛ بدون # و با همان حروف بزرگ و کوچک. عنوان کلید API نام متغیر نیست.</small>
                        @endif

                        @if ($field['type'] === 'secret')
                            <small class="text-muted">{{ $field['stored'] ? 'کلید ذخیره شده است؛ مقدار آن نمایش داده نمی‌شود.' : 'هنوز کلیدی ذخیره نشده است.' }}</small>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="clear[{{ $driver['name'] }}][{{ $field['key'] }}]" value="1" id="sms-clear-{{ $driver['name'] }}-{{ $field['key'] }}" @disabled(! $editable)>
                                <label class="form-check-label" for="sms-clear-{{ $driver['name'] }}-{{ $field['key'] }}">حذف کلید ذخیره‌شده</label>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($driver['name'] === 'smsir')
                    <div class="alert alert-info mb-0">
                        عنوان کلید API (مثلاً «otp»)، شناسه قالب و نام متغیر سه چیز جدا هستند. شناسه عددی <span dir="ltr">templateId</span> و نام دقیق متغیر کد را از بخش قالب‌های sms.ir بردارید؛ تا زمان تکمیل تنظیمات، درایور را فعال نکنید.
                        اگر نام متغیر با متن قالب تطابق نداشته باشد، ممکن است پیامک با متن جایگزین‌نشده مثل <code dir="ltr">#CODE#</code> برسد. پذیرش درخواست توسط sms.ir به معنی جایگزینی صحیح متغیر یا تحویل پیامک به گوشی نیست.
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    @if ($editable)
        <button type="submit" class="btn btn-primary">ذخیره تنظیمات پیامک</button>
    @endif
</form>
@endsection
