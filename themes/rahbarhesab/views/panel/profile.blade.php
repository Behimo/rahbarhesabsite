@extends('theme::layouts.panel')

@section('title', 'پروفایل')

@php
    $firstName = old('first_name', $user->first_name ?: $user->name);
    $lastName = old('last_name', $user->last_name);
    $phone = old('phone', $user->mobile ?: $user->phone);
    $email = old('email', $user->email);
    $identityErrors = $errors->getBag('default');
    $passwordErrors = $errors->getBag('password');
    $roleLabels = [
        'user' => 'هنرجو',
        'instructor' => 'مدرس',
        'editor' => 'ویراستار',
        'shop_manager' => 'مدیر فروشگاه',
        'admin' => 'مدیر',
    ];
    $roleLabel = $roleLabels[$user->role] ?? 'هنرجو';
@endphp

@section('content')
<div class="profile-page">
    <header class="profile-intro">
        <h1 class="profile-intro__title">پرونده من</h1>
        <p class="profile-intro__lede">نام، تماس و رمز ورود این حساب را از همین صفحه به‌روز کنید.</p>
    </header>

    <div class="profile-sheet">
        <div class="profile-sheet__spine" aria-hidden="true">
            <span></span><span></span><span></span>
        </div>

        <section class="profile-identity" aria-labelledby="profile-identity-name">
            <div class="profile-stamp" aria-hidden="true">
                <span class="profile-stamp__ring"></span>
                <span class="profile-stamp__initials">{{ $user->initials() }}</span>
            </div>

            <div class="profile-identity__copy">
                <h2 id="profile-identity-name" class="profile-identity__name">{{ $user->displayName() }}</h2>
                <p class="profile-identity__role">{{ $roleLabel }} راهبر حساب</p>

                <ul class="profile-meta">
                    <li>
                        @if ($user->mobile_verified_at)
                            <span class="profile-chip profile-chip--ok">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                موبایل تأییدشده
                            </span>
                        @else
                            <span class="profile-chip">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                موبایل در انتظار تأیید
                            </span>
                        @endif
                    </li>
                    <li>
                        <span class="profile-chip">عضو از {{ fa_date($user->created_at) }}</span>
                    </li>
                    <li>
                        <span class="profile-chip">{{ fa_digits($enrollmentsCount) }} دوره</span>
                    </li>
                    <li>
                        <span class="profile-chip">{{ fa_digits($ordersCount) }} سفارش</span>
                    </li>
                </ul>
            </div>
        </section>

        <form method="POST" action="{{ route('panel.profile.update') }}" class="profile-chapter" novalidate>
            @csrf
            @method('PUT')

            <div class="profile-chapter__head">
                <h3 class="profile-chapter__title">مشخصات</h3>
                <p class="profile-chapter__hint">این نام در پنل و دوره‌ها نمایش داده می‌شود.</p>
            </div>

            @if ($identityErrors->any())
                <div
                    id="profile-error-summary"
                    class="profile-alert profile-alert--error"
                    role="alert"
                    tabindex="-1"
                    aria-labelledby="profile-error-title"
                >
                    <h4 id="profile-error-title">یک مشکل در مشخصات وجود دارد</h4>
                    <ul>
                        @foreach ($identityErrors->getMessages() as $field => $messages)
                            <li><a href="#field-{{ $field }}">{{ $messages[0] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="profile-ledger">
                <div class="profile-row">
                    <label class="profile-label" for="field-first_name">نام</label>
                    <div class="profile-control">
                        <input
                            id="field-first_name"
                            class="profile-input @error('first_name') profile-input--invalid @enderror"
                            type="text"
                            name="first_name"
                            value="{{ $firstName }}"
                            required
                            autocomplete="given-name"
                            maxlength="100"
                            aria-describedby="{{ $errors->has('first_name') ? 'error-first_name' : 'hint-first_name' }}"
                            @error('first_name') aria-invalid="true" @enderror
                        >
                        <p id="hint-first_name" class="profile-help">نام کوچک، همان‌طور که روی مدارک می‌نویسید.</p>
                        @error('first_name')
                            <p id="error-first_name" class="profile-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="profile-row">
                    <label class="profile-label" for="field-last_name">نام خانوادگی</label>
                    <div class="profile-control">
                        <input
                            id="field-last_name"
                            class="profile-input @error('last_name') profile-input--invalid @enderror"
                            type="text"
                            name="last_name"
                            value="{{ $lastName }}"
                            autocomplete="family-name"
                            maxlength="100"
                            @error('last_name') aria-invalid="true" aria-describedby="error-last_name" @enderror
                        >
                        @error('last_name')
                            <p id="error-last_name" class="profile-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="profile-row">
                    <label class="profile-label" for="field-phone">موبایل</label>
                    <div class="profile-control">
                        <input
                            id="field-phone"
                            class="profile-input profile-input--ltr @error('phone') profile-input--invalid @enderror"
                            type="tel"
                            name="phone"
                            value="{{ $phone }}"
                            required
                            inputmode="numeric"
                            autocomplete="tel"
                            dir="ltr"
                            maxlength="20"
                            aria-describedby="{{ $errors->has('phone') ? 'error-phone' : 'hint-phone' }}"
                            @error('phone') aria-invalid="true" @enderror
                        >
                        <p id="hint-phone" class="profile-help">همین شماره برای ورود با پیامک استفاده می‌شود.</p>
                        @error('phone')
                            <p id="error-phone" class="profile-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="profile-row">
                    <label class="profile-label" for="field-email">ایمیل</label>
                    <div class="profile-control">
                        <input
                            id="field-email"
                            class="profile-input profile-input--ltr @error('email') profile-input--invalid @enderror"
                            type="email"
                            name="email"
                            value="{{ $email }}"
                            autocomplete="email"
                            dir="ltr"
                            maxlength="255"
                            aria-describedby="{{ $errors->has('email') ? 'error-email' : 'hint-email' }}"
                            @error('email') aria-invalid="true" @enderror
                        >
                        <p id="hint-email" class="profile-help">برای رسید خرید و ورود با رمز عبور.</p>
                        @error('email')
                            <p id="error-email" class="profile-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="profile-actions">
                <button type="submit" class="profile-submit">ذخیره مشخصات</button>
            </div>
        </form>

        <form method="POST" action="{{ route('panel.profile.password') }}" class="profile-chapter profile-chapter--security" novalidate>
            @csrf
            @method('PUT')

            <div class="profile-chapter__head">
                <h3 class="profile-chapter__title">رمز ورود با ایمیل</h3>
                <p class="profile-chapter__hint">اگر بخواهید علاوه بر پیامک با ایمیل وارد شوید، اینجا یک رمز بگذارید.</p>
            </div>

            @if ($passwordErrors->any())
                <div
                    id="profile-password-error-summary"
                    class="profile-alert profile-alert--error"
                    role="alert"
                    tabindex="-1"
                    aria-labelledby="profile-password-error-title"
                >
                    <h4 id="profile-password-error-title">رمز عبور ذخیره نشد</h4>
                    <ul>
                        @foreach ($passwordErrors->getMessages() as $field => $messages)
                            <li><a href="#field-{{ $field }}">{{ $messages[0] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="profile-ledger">
                <div class="profile-row">
                    <label class="profile-label" for="field-password">رمز جدید</label>
                    <div class="profile-control">
                        <div class="profile-secret">
                            <input
                                id="field-password"
                                class="profile-input @if ($passwordErrors->has('password')) profile-input--invalid @endif"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                minlength="8"
                                required
                                aria-describedby="{{ $passwordErrors->has('password') ? 'error-password' : 'hint-password' }}"
                                @if ($passwordErrors->has('password')) aria-invalid="true" @endif
                            >
                            <button
                                type="button"
                                class="profile-secret__toggle"
                                data-secret-toggle
                                aria-controls="field-password"
                                aria-pressed="false"
                                aria-label="نمایش رمز"
                            >
                                <svg data-icon-show viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg data-icon-hide hidden viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M9.88 9.88A3 3 0 0012 15a3 3 0 002.12-.88M6.6 6.6C4.5 8.04 3 12 3 12s3.75 6.75 9.75 6.75c1.7 0 3.23-.4 4.5-1.05M17.4 17.4C19.5 15.96 21 12 21 12s-3.75-6.75-9.75-6.75c-.7 0-1.37.07-2 .2"/></svg>
                            </button>
                        </div>
                        <p id="hint-password" class="profile-help">حداقل ۸ کاراکتر. می‌توانید رمز را از مدیر رمز عبور جای‌گذاری کنید.</p>
                        @if ($passwordErrors->has('password'))
                            <p id="error-password" class="profile-error">{{ $passwordErrors->first('password') }}</p>
                        @endif
                    </div>
                </div>

                <div class="profile-row">
                    <label class="profile-label" for="field-password_confirmation">تکرار رمز</label>
                    <div class="profile-control">
                        <div class="profile-secret">
                            <input
                                id="field-password_confirmation"
                                class="profile-input @if ($passwordErrors->has('password_confirmation')) profile-input--invalid @endif"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                minlength="8"
                                required
                                @if ($passwordErrors->has('password_confirmation')) aria-invalid="true" aria-describedby="error-password_confirmation" @endif
                            >
                            <button
                                type="button"
                                class="profile-secret__toggle"
                                data-secret-toggle
                                aria-controls="field-password_confirmation"
                                aria-pressed="false"
                                aria-label="نمایش تکرار رمز"
                            >
                                <svg data-icon-show viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg data-icon-hide hidden viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M9.88 9.88A3 3 0 0012 15a3 3 0 002.12-.88M6.6 6.6C4.5 8.04 3 12 3 12s3.75 6.75 9.75 6.75c1.7 0 3.23-.4 4.5-1.05M17.4 17.4C19.5 15.96 21 12 21 12s-3.75-6.75-9.75-6.75c-.7 0-1.37.07-2 .2"/></svg>
                            </button>
                        </div>
                        @if ($passwordErrors->has('password_confirmation'))
                            <p id="error-password_confirmation" class="profile-error">{{ $passwordErrors->first('password_confirmation') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="profile-actions">
                <button type="submit" class="profile-submit profile-submit--quiet">به‌روزرسانی رمز عبور</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-secret-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('aria-controls'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.classList.toggle('is-revealed', show);
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? 'پنهان کردن رمز' : (input.id === 'field-password_confirmation' ? 'نمایش تکرار رمز' : 'نمایش رمز'));
        });
    });

    var summary = document.getElementById('profile-password-error-summary') || document.getElementById('profile-error-summary');
    if (summary) summary.focus();
</script>
@endpush
