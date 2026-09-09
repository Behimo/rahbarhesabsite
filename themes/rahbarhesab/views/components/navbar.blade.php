<header class="rh-header sticky top-0 z-50">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            <img src="{{ \App\Models\CmsSetting::get('site_logo', config('cms.branding.logo')) }}" alt="راهبر حساب" class="h-10 w-auto">
        </a>

        <nav class="hidden items-center gap-6 lg:flex">
            @foreach ($navLinks as $link)
                @php
                    $href = isset($link['route'])
                        ? (isset($link['params']) ? route($link['route'], $link['params']) : route($link['route']))
                        : ($link['href'] ?? '#');
                @endphp
                <a href="{{ $href }}" class="text-sm font-medium text-slate-600 hover:text-teal-700">{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-3">
            <a href="{{ route('cart.index') }}" class="text-slate-600 hover:text-teal-700" title="سبد خرید">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </a>
            @auth
                <a href="{{ route('panel.dashboard') }}" class="rh-btn-primary text-sm">پنل کاربری</a>
            @else
                <a href="{{ route('login') }}" class="rh-btn-primary text-sm">ورود</a>
            @endauth
        </div>
    </div>
</header>
