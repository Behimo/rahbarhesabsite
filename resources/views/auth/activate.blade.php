@extends('layouts.site')

@section('page')
@php
    $logo = \App\Models\CmsSetting::get('site_logo') ?: asset('site/images/logorahbarhesab.webp');
@endphp

<section class="rh-auth rh-auth--verify" aria-labelledby="rh-auth-activate-title" data-rh-auth>
    <div class="rh-auth__stage rh-auth__stage--narrow">
        <aside class="rh-auth__brand rh-auth__brand--compact">
            <div class="rh-auth__brand-glow" aria-hidden="true"></div>
            <div class="rh-auth__brand-lines" aria-hidden="true"></div>

            <a href="{{ route('home') }}" class="rh-auth__logo">
                <img src="{{ $logo }}" alt="{{ config('cms.site_name_fa') }}">
            </a>

            <p class="rh-auth__brand-kicker">فعال‌سازی حساب</p>
            <h1 id="rh-auth-activate-title" class="rh-auth__brand-title">شماره موبایل را تأیید کنید</h1>
            <p class="rh-auth__brand-lede">حساب «{{ $user->displayName() }}» تا تأیید موبایل مثل مهمان است و به پنل دسترسی ندارد.</p>
        </aside>

        <div class="rh-auth__panel">
            <div class="rh-auth__sheet">
                <div class="rh-auth__spine" aria-hidden="true"><span></span><span></span><span></span></div>

                <div class="rh-auth__panel-head">
                    <h2 class="rh-auth__panel-title">ثبت موبایل</h2>
                    <p class="rh-auth__panel-hint">کد یک‌بارمصرف به این شماره ارسال می‌شود.</p>
                </div>

                @if (session('success'))
                    <div class="rh-auth__alert rh-auth__alert--ok" role="status">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="rh-auth__alert rh-auth__alert--error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.activate.submit') }}" class="rh-auth__form" data-auth-form novalidate>
                    @csrf
                    <div class="rh-auth__field">
                        <label for="auth-activate-phone">شماره موبایل</label>
                        <input
                            type="tel"
                            id="auth-activate-phone"
                            name="phone"
                            value="{{ old('phone', $suggestedMobile) }}"
                            inputmode="numeric"
                            autocomplete="tel"
                            placeholder="09123456789"
                            required
                            dir="ltr"
                            @error('phone') aria-invalid="true" @enderror
                        >
                        @error('phone')
                            <p class="rh-auth__field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rh-auth__submit">ارسال کد تأیید</button>
                </form>

                <p class="rh-auth__panel-hint mt-3">
                    <a href="{{ route('login') }}">بازگشت به ورود</a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
