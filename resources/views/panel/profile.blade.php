@extends('layouts.panel')

@section('title', 'پروفایل')

@section('content')
<h1 class="mb-6 text-2xl font-bold text-white">پروفایل</h1>

@if (session('success'))
    <div class="mb-4 rounded-lg bg-green-500/20 p-3 text-green-200">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('panel.profile.update') }}" class="max-w-lg space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="mb-1 block text-sm text-gray-300">نام</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
            class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white">
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="mb-1 block text-sm text-gray-300">نام</label>
            <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white">
        </div>
        <div>
            <label class="mb-1 block text-sm text-gray-300">نام خانوادگی</label>
            <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white">
        </div>
    </div>
    <div>
        <label class="mb-1 block text-sm text-gray-300">ایمیل</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}"
            class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white">
    </div>
    <div>
        <label class="mb-1 block text-sm text-gray-300">موبایل</label>
        <input type="text" name="phone" value="{{ old('phone', $user->mobile ?: $user->phone) }}"
            class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white">
    </div>
    <button type="submit" class="btn-demo">ذخیره تغییرات</button>
</form>
@endsection
