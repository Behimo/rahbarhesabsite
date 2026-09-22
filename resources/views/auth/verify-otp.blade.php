@extends('layouts.app')

@section('content')
@php $otpLength = (int) config('otp.length', 6); @endphp

<section class="mx-auto max-w-md px-4 py-16" data-rh-auth>
    <h1 class="mb-2 text-2xl font-bold">تأیید کد</h1>
    <p class="mb-6 text-sm text-slate-600">کد ارسال‌شده به <span dir="ltr">{{ $phone }}</span> را وارد کنید</p>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ $errors->first() }}</div>
    @endif
    @if (!empty($devCode) && app()->environment('local', 'testing'))
        <div class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800" role="status">کد تست: <strong dir="ltr">{{ $devCode }}</strong></div>
    @endif

    <form method="POST" action="{{ route('login.verify.submit') }}" class="space-y-4" data-auth-form>
        @csrf
        <div>
            <label for="fallback-code" class="mb-1 block text-sm font-medium">کد {{ $otpLength }} رقمی</label>
            <input id="fallback-code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="{{ $otpLength }}" required dir="ltr" class="w-full rounded-xl border px-4 py-3 text-center text-2xl tracking-widest">
        </div>
        <button type="submit" class="w-full rounded-xl bg-violet-700 px-4 py-3 font-semibold text-white" data-auth-submit>
            <span data-auth-submit-label>تأیید و ورود</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="font-semibold text-violet-700 underline">تغییر شماره موبایل</a>
    </p>
</section>
@endsection
