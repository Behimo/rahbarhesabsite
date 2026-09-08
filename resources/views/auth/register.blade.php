@extends('layouts.site')

@section('page')
<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <div class="rounded-2xl border border-white/10 bg-white/5 p-8 backdrop-blur">
        <h1 class="mb-6 text-2xl font-bold text-white">ایجاد حساب کاربری</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-500/20 p-3 text-sm text-red-200">
                <ul class="list-disc ps-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm text-gray-300">نام</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white focus:border-orange-500 focus:outline-none">
            </div>
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
            <div>
                <label class="mb-1 block text-sm text-gray-300">تکرار رمز عبور</label>
                <input type="password" name="password_confirmation" required
                    class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white focus:border-orange-500 focus:outline-none">
            </div>
            <button type="submit" class="btn-demo w-full justify-center">ثبت‌نام</button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-400">
            قبلاً ثبت‌نام کرده‌اید؟ <a href="{{ route('login') }}" class="text-orange-400 hover:underline">ورود</a>
        </p>
    </div>
</div>
@endsection
