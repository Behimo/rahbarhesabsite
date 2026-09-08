@extends('layouts.site')

@section('page')
<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <div class="rounded-2xl border border-white/10 bg-white/5 p-8 backdrop-blur">
        <h1 class="mb-2 text-2xl font-bold text-white">تأیید کد</h1>
        <p class="mb-6 text-sm text-gray-400">کد ارسال‌شده به {{ $phone }} را وارد کنید</p>

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-500/20 p-3 text-sm text-green-200">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-500/20 p-3 text-sm text-red-200">{{ $errors->first() }}</div>
        @endif

        @if ($devCode && app()->environment('local', 'testing'))
            <div class="mb-4 rounded-lg bg-amber-500/20 p-3 text-sm text-amber-200">کد تست: {{ $devCode }}</div>
        @endif

        <form method="POST" action="{{ route('login.verify.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm text-gray-300">کد {{ config('otp.length', 6) }} رقمی</label>
                <input type="text" name="code" inputmode="numeric" maxlength="{{ config('otp.length', 6) }}" required dir="ltr"
                    class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-center text-2xl tracking-widest text-white focus:border-orange-500 focus:outline-none">
            </div>
            <button type="submit" class="btn-demo w-full justify-center">ورود</button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-400">
            <a href="{{ route('login') }}" class="text-orange-400 hover:underline">تغییر شماره موبایل</a>
        </p>
    </div>
</div>
@endsection
