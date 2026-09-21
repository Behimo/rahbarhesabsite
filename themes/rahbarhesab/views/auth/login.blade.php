@extends('theme::layouts.site')

@section('page')
    <div class="mx-auto max-w-md px-4 py-16 sm:px-6">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-8 backdrop-blur">
            <h1 class="mb-2 text-2xl font-bold text-white">ورود با موبایل</h1>
            <p class="mb-6 text-sm text-gray-400">کد تأیید به شماره موبایل شما ارسال می‌شود</p>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-500/20 p-3 text-sm text-red-200">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.otp') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm text-gray-300">شماره موبایل</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="09123456789" required dir="ltr"
                        class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white focus:border-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-300">نام (برای ثبت‌نام اول)</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white focus:border-orange-500 focus:outline-none">
                </div>
                <button type="submit" class="btn-demo w-full justify-center">ارسال کد تأیید</button>
            </form>

            <div class="my-6 border-t border-white/10 pt-6">
                <p class="mb-4 text-sm text-gray-400">ورود با ایمیل یا موبایل و رمز عبور</p>
                <form method="POST" action="{{ route('login.password') }}" class="space-y-4">
                    @csrf
                    <input type="text" name="login" value="{{ old('login') }}" required dir="ltr" placeholder="ایمیل یا موبایل"
                        class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white">
                    <input type="password" name="password" required placeholder="رمز عبور"
                        class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white">
                    <button type="submit" class="w-full rounded-xl border border-white/20 px-4 py-3 text-white hover:bg-white/10">ورود با رمز عبور</button>
                </form>
            </div>
        </div>
    </div>
@endsection
