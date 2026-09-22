@extends('theme::layouts.site')

@section('page')
@php
    $logo = \App\Models\CmsSetting::get('site_logo') ?: theme_asset('images/logorahbarhesab.webp');
    $otpLength = (int) config('otp.length', 6);
@endphp

<section class="rh-auth rh-auth--verify" aria-labelledby="rh-auth-verify-title" data-rh-auth>
    <div class="rh-auth__stage rh-auth__stage--narrow">
        <aside class="rh-auth__brand rh-auth__brand--compact">
            <div class="rh-auth__brand-glow" aria-hidden="true"></div>
            <div class="rh-auth__brand-lines" aria-hidden="true"></div>

            <a href="{{ route('home') }}" class="rh-auth__logo">
                <img src="{{ $logo }}" alt="{{ config('cms.site_name_fa') }}">
            </a>

            <p class="rh-auth__brand-kicker">تأیید هویت</p>
            <h1 id="rh-auth-verify-title" class="rh-auth__brand-title">کد را وارد کنید</h1>
            <p class="rh-auth__brand-lede">پیامک به شماره <strong dir="ltr">{{ $phone }}</strong> ارسال شد.</p>
        </aside>

        <div class="rh-auth__panel">
            <div class="rh-auth__sheet">
                <div class="rh-auth__spine" aria-hidden="true"><span></span><span></span><span></span></div>

                <div class="rh-auth__panel-head">
                    <h2 class="rh-auth__panel-title">کد تأیید</h2>
                    <p class="rh-auth__panel-hint">کد {{ fa_digits($otpLength) }} رقمی را وارد کنید.</p>
                </div>

                @if (session('success'))
                    <div class="rh-auth__alert rh-auth__alert--ok" role="status">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="rh-auth__alert rh-auth__alert--error" role="alert">{{ $errors->first() }}</div>
                @endif

                @if (!empty($devCode) && app()->environment('local', 'testing'))
                    <div class="rh-auth__alert rh-auth__alert--dev" role="status">کد تست: <strong dir="ltr">{{ $devCode }}</strong></div>
                @endif

                <form method="POST" action="{{ route('login.verify.submit') }}" class="rh-auth__form" data-auth-form novalidate>
                    @csrf
                    <div class="rh-auth__field">
                        <label for="auth-code">کد {{ fa_digits($otpLength) }} رقمی</label>
                        <input
                            type="text"
                            id="auth-code"
                            name="code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="{{ $otpLength }}"
                            required
                            dir="ltr"
                            class="rh-auth__otp"
                            @error('code') aria-invalid="true" @enderror
                        >
                        @error('code')
                            <p class="rh-auth__field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rh-auth__submit" data-auth-submit>
                        <span data-auth-submit-label>تأیید و ورود</span>
                        <span class="rh-auth__spinner" data-auth-spinner hidden aria-hidden="true"></span>
                    </button>
                </form>

                <p class="rh-auth__footer-link">
                    <a href="{{ route('login') }}">تغییر شماره موبایل</a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
