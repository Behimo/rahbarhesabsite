<header
    class="site-header"
    x-data="{
        scrolled: false,
        init() {
            const onScroll = () => { this.scrolled = window.scrollY > 24; };
            onScroll();
            window.addEventListener('scroll', onScroll, { passive: true });
        }
    }"
    :class="{ 'site-header--scrolled': scrolled }"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="flex h-16 items-center justify-between lg:h-20" aria-label="ناوبری اصلی">
            <a href="{{ route('home') }}" class="group shrink-0">
                <x-logo size="sm" />
            </a>

            <div class="hidden items-center gap-1 lg:flex">
                @foreach (($navLinks ?? []) as $link)
                    @php
                        $href = isset($link['route'])
                            ? (isset($link['params']) ? route($link['route'], $link['params']) : route($link['route']))
                            : ($link['href'] ?? '#');
                        $isActive = isset($link['route']) && request()->routeIs($link['route'].(isset($link['params']) ? '' : ''));
                        if (isset($link['params']['slug'])) {
                            $isActive = request()->routeIs('pages.show') && request()->route('slug') === $link['params']['slug'];
                        }
                    @endphp
                    <a
                        href="{{ $href }}"
                        class="nav-link {{ $isActive ? 'nav-link--active' : '' }}"
                    >
                        <span>{{ $link['label'] }}</span>
                        <span class="nav-link__dot"></span>
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <button
                    type="button"
                    class="theme-toggle hidden sm:inline-flex"
                    @click="$store.theme.toggle()"
                    :aria-label="$store.theme.mode === 'dark' ? 'فعال‌سازی حالت روشن' : 'فعال‌سازی حالت تاریک'"
                    :title="$store.theme.mode === 'dark' ? 'حالت روشن' : 'حالت تاریک'"
                >
                    <svg x-show="$store.theme.mode === 'dark'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg x-show="$store.theme.mode === 'light'" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                <a href="{{ route('cart.index') }}" class="relative hidden rounded-xl border border-white/10 p-2 text-white sm:inline-flex" title="سبد خرید">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </a>

                @auth
                    <a href="{{ route('panel.dashboard') }}" class="hidden rounded-xl border border-white/10 px-3 py-2 text-sm text-white sm:inline-flex">پنل کاربری</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-xl border border-white/10 px-3 py-2 text-sm text-white sm:inline-flex">ورود</a>
                @endauth

                <a href="{{ route('contact') }}" class="btn-demo hidden sm:inline-flex" data-magnetic>
                    <span>مشاوره رایگان</span>
                    <svg class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>

                <button
                    type="button"
                    class="theme-toggle sm:hidden"
                    @click="$store.theme.toggle()"
                    :aria-label="$store.theme.mode === 'dark' ? 'فعال‌سازی حالت روشن' : 'فعال‌سازی حالت تاریک'"
                >
                    <svg x-show="$store.theme.mode === 'dark'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg x-show="$store.theme.mode === 'light'" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-white/10 p-2 text-white lg:hidden"
                    @click="mobileMenu = !mobileMenu"
                    :aria-expanded="mobileMenu"
                    aria-label="منوی موبایل"
                >
                    <svg x-show="!mobileMenu" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenu" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </nav>

        <div x-show="mobileMenu" x-cloak x-transition class="glass-nav mb-4 rounded-2xl p-4 lg:hidden">
            <div class="flex flex-col gap-1">
                @foreach (($navLinks ?? []) as $link)
                    @php
                        $href = isset($link['route'])
                            ? (isset($link['params']) ? route($link['route'], $link['params']) : route($link['route']))
                            : ($link['href'] ?? '#');
                    @endphp
                    <a href="{{ $href }}" @click="mobileMenu = false" class="mobile-nav-link rounded-xl px-4 py-3 text-sm text-slate-300">
                        {{ $link['label'] }}
                    </a>
                @endforeach
                <a href="{{ route('contact') }}" @click="mobileMenu = false" class="btn-demo mt-2 w-full justify-center">مشاوره رایگان</a>
                <button
                    type="button"
                    class="theme-toggle mt-2 w-full justify-center rounded-xl py-2.5"
                    @click="$store.theme.toggle()"
                >
                    <span x-text="$store.theme.mode === 'dark' ? 'حالت روشن' : 'حالت تاریک'"></span>
                </button>
            </div>
        </div>
    </div>
</header>
