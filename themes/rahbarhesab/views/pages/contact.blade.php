@extends('theme::layouts.site')

@section('page')
<section class="bg-teal-800 py-12 text-white">
    <div class="mx-auto max-w-5xl px-4 text-center sm:px-6">
        <h1 class="text-3xl font-extrabold">تماس با ما</h1>
        <p class="mt-2 text-teal-100">در ۲۴ ساعت کاری پاسخ می‌دهیم</p>
    </div>
</section>

<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
    @endif

    <div class="grid gap-8 lg:grid-cols-5">
        <div class="space-y-4 lg:col-span-2">
            <div class="rh-card p-6">
                <h2 class="mb-2 font-bold text-slate-800">ایمیل</h2>
                <a href="mailto:{{ $contact['email'] ?? '' }}" class="text-teal-700 hover:underline">{{ $contact['email'] ?? '' }}</a>
            </div>
            @if (!empty($contact['phone']))
                <div class="rh-card p-6">
                    <h2 class="mb-2 font-bold text-slate-800">تلفن</h2>
                    <a href="tel:{{ $contact['phone'] }}" class="text-teal-700 hover:underline" dir="ltr">{{ $contact['phone'] }}</a>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('contact.store') }}" class="rh-card space-y-4 p-6 lg:col-span-3">
            @csrf
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">نام *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-teal-600 focus:outline-none">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">ایمیل *</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required dir="ltr" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-teal-600 focus:outline-none">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">تلفن</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" dir="ltr" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-teal-600 focus:outline-none">
            </div>
            <div>
                <label for="subject" class="mb-1 block text-sm font-medium text-slate-700">موضوع</label>
                <input type="text" id="subject" name="subject" value="{{ old('subject', request('product')) }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-teal-600 focus:outline-none">
            </div>
            <div>
                <label for="message" class="mb-1 block text-sm font-medium text-slate-700">پیام *</label>
                <textarea id="message" name="message" rows="5" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-teal-600 focus:outline-none">{{ old('message') }}</textarea>
                @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="w-full rounded-xl bg-teal-700 px-6 py-3 font-semibold text-white hover:bg-teal-800">ارسال پیام</button>
        </form>
    </div>
</div>
@endsection
