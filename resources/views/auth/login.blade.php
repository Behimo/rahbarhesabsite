@extends('layouts.app')

@section('content')
@php
    $hasPasswordErrors = $errors->has('login') || $errors->has('password');
    $activeTab = $hasPasswordErrors ? 'password' : 'otp';
@endphp

<section class="mx-auto max-w-md px-4 py-16" data-rh-auth data-active-tab="{{ $activeTab }}">
    <h1 class="mb-2 text-2xl font-bold">ورود / ثبت‌نام</h1>
    <p class="mb-6 text-sm text-slate-600">کد تأیید به شماره موبایل شما ارسال می‌شود</p>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="mb-4 flex gap-2" role="tablist">
        <button type="button" class="flex-1 rounded-lg border px-3 py-2 text-sm font-semibold{{ $activeTab === 'otp' ? ' bg-violet-700 text-white' : '' }}" data-auth-tab="otp" role="tab" aria-selected="{{ $activeTab === 'otp' ? 'true' : 'false' }}">ورود با موبایل</button>
        <button type="button" class="flex-1 rounded-lg border px-3 py-2 text-sm font-semibold{{ $activeTab === 'password' ? ' bg-violet-700 text-white' : '' }}" data-auth-tab="password" role="tab" aria-selected="{{ $activeTab === 'password' ? 'true' : 'false' }}">ورود با رمز عبور</button>
    </div>

    <div data-auth-panel="otp" @if ($activeTab !== 'otp') hidden @endif>
        <form method="POST" action="{{ route('login.otp') }}" class="space-y-4" data-auth-form>
            @csrf
            <div>
                <label for="fallback-phone" class="mb-1 block text-sm font-medium">شماره موبایل</label>
                <input id="fallback-phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="09123456789" required dir="ltr" autocomplete="tel" class="w-full rounded-xl border px-4 py-3">
            </div>
            <div>
                <label for="fallback-name" class="mb-1 block text-sm font-medium">نام (برای ثبت‌نام اول)</label>
                <input id="fallback-name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" class="w-full rounded-xl border px-4 py-3">
            </div>
            <button type="submit" class="w-full rounded-xl bg-violet-700 px-4 py-3 font-semibold text-white" data-auth-submit>
                <span data-auth-submit-label>ارسال کد تأیید</span>
            </button>
        </form>
    </div>

    <div data-auth-panel="password" @if ($activeTab !== 'password') hidden @endif>
        <form method="POST" action="{{ route('login.password') }}" class="space-y-4" data-auth-form>
            @csrf
            <div>
                <label for="fallback-login" class="mb-1 block text-sm font-medium">ایمیل یا موبایل</label>
                <input id="fallback-login" type="text" name="login" value="{{ old('login') }}" required dir="ltr" autocomplete="username" class="w-full rounded-xl border px-4 py-3">
            </div>
            <div>
                <label for="fallback-password" class="mb-1 block text-sm font-medium">رمز عبور</label>
                <input id="fallback-password" type="password" name="password" required autocomplete="current-password" class="w-full rounded-xl border px-4 py-3">
            </div>
            <button type="submit" class="w-full rounded-xl border px-4 py-3 font-semibold" data-auth-submit>
                <span data-auth-submit-label>ورود با رمز عبور</span>
            </button>
        </form>
    </div>
</section>
@endsection
