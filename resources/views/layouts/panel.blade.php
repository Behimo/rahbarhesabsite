@extends('layouts.site')

@section('page')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-8 lg:grid-cols-4">
        <aside class="lg:col-span-1">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                <div class="mb-4 border-b border-white/10 pb-4">
                    <p class="font-semibold text-white">{{ auth()->user()->name }}</p>
                    <p class="text-sm text-gray-400">{{ auth()->user()->email }}</p>
                </div>
                <nav class="space-y-1">
                    <a href="{{ route('panel.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('panel.dashboard') ? 'bg-orange-500/20 text-orange-300' : 'text-gray-300 hover:bg-white/5' }}">داشبورد</a>
                    <a href="{{ route('panel.courses') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('panel.courses') ? 'bg-orange-500/20 text-orange-300' : 'text-gray-300 hover:bg-white/5' }}">دوره‌های من</a>
                    <a href="{{ route('panel.orders') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('panel.orders') ? 'bg-orange-500/20 text-orange-300' : 'text-gray-300 hover:bg-white/5' }}">سفارش‌ها</a>
                    <a href="{{ route('panel.profile') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('panel.profile') ? 'bg-orange-500/20 text-orange-300' : 'text-gray-300 hover:bg-white/5' }}">پروفایل</a>
                    <form method="POST" action="{{ route('logout') }}" class="pt-2">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2 text-start text-sm text-red-300 hover:bg-red-500/10">خروج</button>
                    </form>
                </nav>
            </div>
        </aside>
        <main class="lg:col-span-3">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-500/20 p-3 text-sm text-green-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-500/20 p-3 text-sm text-red-200">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@endsection
