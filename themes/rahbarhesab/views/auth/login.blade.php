@extends('theme::layouts.site')

@section('page')
@php
    $hasPasswordErrors = $errors->has('login') || $errors->has('password');
    $activeTab = $hasPasswordErrors ? 'password' : 'otp';
    $logo = \App\Models\CmsSetting::get('site_logo') ?: theme_asset('images/logorahbarhesab.webp');
@endphp

<section class="rh-auth" aria-labelledby="rh-auth-title" data-rh-auth data-active-tab="{{ $activeTab }}">
    <div class="rh-auth__stage">
        <aside class="rh-auth__brand" aria-hidden="false">
            <div class="rh-auth__brand-glow" aria-hidden="true"></div>
            <div class="rh-auth__brand-lines" aria-hidden="true"></div>

            <a href="{{ route('home') }}" class="rh-auth__logo">
                <img src="{{ $logo }}" alt="{{ config('cms.site_name_fa') }}">
            </a>

            <p class="rh-auth__brand-kicker">پرونده آموزشی شما</p>
            <h1 id="rh-auth-title" class="rh-auth__brand-title">ورود به راهبر حساب</h1>
            <p class="rh-auth__brand-lede">با شماره موبایل وارد شوید یا ثبت‌نام کنید؛ کد تأیید همان لحظه ارسال می‌شود.</p>

            <ul class="rh-auth__trust">
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    ورود امن با کد یک‌بارمصرف
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    دسترسی به دوره‌ها و پنل هنرجو
                </li>
            </ul>
        </aside>

        <div class="rh-auth__panel">
            <div class="rh-auth__sheet">
                <div class="rh-auth__spine" aria-hidden="true"><span></span><span></span><span></span></div>

                <div class="rh-auth__panel-head">
                    <h2 class="rh-auth__panel-title">ورود / ثبت‌نام</h2>
                    <p class="rh-auth__panel-hint">اگر اولین بار است، با موبایل ثبت‌نام می‌شوید.</p>
                </div>

                <div class="rh-auth__tabs" role="tablist" aria-label="روش ورود">
                    <button type="button" class="rh-auth__tab{{ $activeTab === 'otp' ? ' is-active' : '' }}" role="tab" id="tab-otp" aria-controls="panel-otp" aria-selected="{{ $activeTab === 'otp' ? 'true' : 'false' }}" data-auth-tab="otp">
                        ورود با موبایل
                    </button>
                    <button type="button" class="rh-auth__tab{{ $activeTab === 'password' ? ' is-active' : '' }}" role="tab" id="tab-password" aria-controls="panel-password" aria-selected="{{ $activeTab === 'password' ? 'true' : 'false' }}" data-auth-tab="password">
                        ورود با رمز عبور
                    </button>
                </div>

                @if ($errors->any())
                    <div class="rh-auth__alert rh-auth__alert--error" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div
                    class="rh-auth__tabpanel{{ $activeTab === 'otp' ? ' is-active' : '' }}"
                    id="panel-otp"
                    role="tabpanel"
                    aria-labelledby="tab-otp"
                    data-auth-panel="otp"
                    @if ($activeTab !== 'otp') hidden @endif
                >
                    <form method="POST" action="{{ route('login.otp') }}" class="rh-auth__form" data-auth-form novalidate>
                        @csrf
                        <div class="rh-auth__field">
                            <label for="auth-phone">شماره موبایل</label>
                            <input
                                type="tel"
                                id="auth-phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                inputmode="numeric"
                                autocomplete="tel"
                                placeholder="09123456789"
                                required
                                dir="ltr"
                                aria-describedby="auth-phone-help"
                                @error('phone') aria-invalid="true" @enderror
                            >
                            <p id="auth-phone-help" class="rh-auth__help">کد ۶ رقمی به این شماره پیامک می‌شود.</p>
                            @error('phone')
                                <p class="rh-auth__field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rh-auth__field">
                            <label for="auth-name">نام <span class="rh-auth__optional">(فقط برای ثبت‌نام اول)</span></label>
                            <input
                                type="text"
                                id="auth-name"
                                name="name"
                                value="{{ old('name') }}"
                                autocomplete="name"
                                placeholder="مثلاً سارا محمدی"
                            >
                        </div>

                        <button type="submit" class="rh-auth__submit" data-auth-submit>
                            <span data-auth-submit-label>ارسال کد تأیید</span>
                            <span class="rh-auth__spinner" data-auth-spinner hidden aria-hidden="true"></span>
                        </button>
                    </form>
                </div>

                <div
                    class="rh-auth__tabpanel{{ $activeTab === 'password' ? ' is-active' : '' }}"
                    id="panel-password"
                    role="tabpanel"
                    aria-labelledby="tab-password"
                    data-auth-panel="password"
                    @if ($activeTab !== 'password') hidden @endif
                >
                    <form method="POST" action="{{ route('login.password') }}" class="rh-auth__form" data-auth-form novalidate>
                        @csrf
                        <p class="rh-auth__note">برای حساب‌هایی که از سایت قبلی منتقل شده‌اند.</p>

                        <div class="rh-auth__field">
                            <label for="auth-login">ایمیل یا موبایل</label>
                            <input
                                type="text"
                                id="auth-login"
                                name="login"
                                value="{{ old('login') }}"
                                autocomplete="username"
                                required
                                dir="ltr"
                                @error('login') aria-invalid="true" @enderror
                            >
                            @error('login')
                                <p class="rh-auth__field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rh-auth__field">
                            <label for="auth-password">رمز عبور</label>
                            <div class="rh-auth__secret">
                                <input
                                    type="password"
                                    id="auth-password"
                                    name="password"
                                    autocomplete="current-password"
                                    required
                                    @error('password') aria-invalid="true" @enderror
                                >
                                <button type="button" class="rh-auth__reveal" data-password-toggle aria-label="نمایش رمز عبور" aria-pressed="false">
                                    <svg data-icon-show viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <svg data-icon-hide hidden viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="rh-auth__field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="rh-auth__submit rh-auth__submit--secondary" data-auth-submit>
                            <span data-auth-submit-label>ورود با رمز عبور</span>
                            <span class="rh-auth__spinner" data-auth-spinner hidden aria-hidden="true"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
