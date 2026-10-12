@extends('layouts.site')

@section('page')
<div class="panel-shell">
    <aside class="panel-sidebar">
        <div class="panel-sidebar__who">
            <p class="panel-sidebar__name">{{ auth()->user()->displayName() }}</p>
            <p class="panel-sidebar__mail" dir="ltr">{{ auth()->user()->email ?: (auth()->user()->mobile ?: auth()->user()->phone) }}</p>
        </div>
        @php $unreadNotifications = auth()->user()->unreadNotifications()->count(); @endphp
        <nav class="panel-nav" aria-label="پنل کاربری">
            <a href="{{ route('panel.dashboard') }}" class="panel-nav__link {{ request()->routeIs('panel.dashboard') ? 'is-active' : '' }}">داشبورد</a>
            <a href="{{ route('panel.courses') }}" class="panel-nav__link {{ request()->routeIs('panel.courses') ? 'is-active' : '' }}">دوره‌های من</a>
            <a href="{{ route('panel.orders') }}" class="panel-nav__link {{ request()->routeIs('panel.orders') ? 'is-active' : '' }}">سفارش‌ها</a>
            <a href="{{ route('panel.questions') }}" class="panel-nav__link {{ request()->routeIs('panel.questions') ? 'is-active' : '' }}">سوالات من</a>
            <a href="{{ route('panel.notifications') }}" class="panel-nav__link {{ request()->routeIs('panel.notifications*') ? 'is-active' : '' }}">
                اعلان‌ها
                @if ($unreadNotifications > 0)
                    <span class="panel-nav__badge">{{ fa_digits($unreadNotifications) }}</span>
                @endif
            </a>
            <a href="{{ route('panel.profile') }}" class="panel-nav__link {{ request()->routeIs('panel.profile') ? 'is-active' : '' }}">پروفایل</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="panel-nav__logout">خروج</button>
            </form>
        </nav>
    </aside>
    <div class="panel-main">
        @if (session('success'))
            <div class="panel-flash panel-flash--ok" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="panel-flash panel-flash--error" role="alert">{{ session('error') }}</div>
        @endif
        @yield('content')
    </div>
</div>
@endsection
