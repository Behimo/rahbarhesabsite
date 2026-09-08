@extends('layouts.panel')

@section('title', 'پروفایل')

@section('content')
<h1 class="mb-6 text-2xl font-bold text-white">پروفایل</h1>

<form method="POST" action="{{ route('panel.profile.update') }}" class="max-w-lg space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="mb-1 block text-sm text-gray-300">نام</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
            class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white">
    </div>
    <div>
        <label class="mb-1 block text-sm text-gray-300">ایمیل</label>
        <input type="email" value="{{ $user->email }}" disabled
            class="w-full rounded-xl border border-white/10 bg-black/20 px-4 py-3 text-gray-400">
    </div>
    <div>
        <label class="mb-1 block text-sm text-gray-300">موبایل</label>
        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
            class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-white">
    </div>
    <button type="submit" class="btn-demo">ذخیره تغییرات</button>
</form>
@endsection
