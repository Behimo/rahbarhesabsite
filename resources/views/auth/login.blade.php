@extends('layouts.site')

@section('page')
<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <div class="rounded-2xl border border-white/10 bg-white/5 p-8 backdrop-blur">
        <h1 class="mb-6 text-2xl font-bold text-white">ورود به حساب</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-500/20 p-3 text-sm text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm text-gray-300">ایمیل</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white focus:border-orange-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm text-gray-300">رمز عبور</label>
                <input type="password" name="password" required
                    class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white focus:border-orange-500 focus:outline-none">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-300">
                <input type="checkbox" name="remember" class="rounded">
                مرا به خاطر بسپار
            </label>
            <button type="submit" class="btn-demo w-full justify-center">ورود</button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-400">
            حساب ندارید؟ <a href="{{ route('register') }}" class="text-orange-400 hover:underline">ثبت‌نام</a>
        </p>
    </div>
</div>
@endsection
