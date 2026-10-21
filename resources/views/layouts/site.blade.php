<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php $seoMeta = $seo ?? app(\App\Services\SeoService::class)->meta(); @endphp

    <title>{{ $seoMeta['title'] ?? config('cms.site_name_fa') }}</title>
    <meta name="description" content="{{ $seoMeta['description'] ?? '' }}">
    @if (!empty($seoMeta['keywords']))
        <meta name="keywords" content="{{ $seoMeta['keywords'] }}">
    @endif
    <meta name="robots" content="{{ $seoMeta['robots'] ?? 'index, follow' }}">
    <link rel="canonical" href="{{ $seoMeta['canonical'] ?? url()->current() }}">
    @if ($gsc = config('seo.analytics.gsc_verification'))
        <meta name="google-site-verification" content="{{ $gsc }}">
    @endif
    @if ($gtm = config('seo.analytics.gtm_id'))
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtm }}');</script>
    @endif

    <meta property="og:type" content="website">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="{{ config('cms.site_name_fa') }}">
    <meta property="og:title" content="{{ $seoMeta['og_title'] ?? $seoMeta['title'] ?? '' }}">
    <meta property="og:description" content="{{ $seoMeta['description'] ?? '' }}">
    <meta property="og:url" content="{{ $seoMeta['canonical'] ?? url()->current() }}">
    @if (!empty($seoMeta['og_image']))
        <meta property="og:image" content="{{ $seoMeta['og_image'] }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoMeta['og_title'] ?? $seoMeta['title'] ?? '' }}">
    <meta name="twitter:description" content="{{ $seoMeta['description'] ?? '' }}">
    @if (!empty($seoMeta['og_image']))
        <meta name="twitter:image" content="{{ $seoMeta['og_image'] }}">
    @endif

    <link rel="icon" href="{{ asset('site/images/logorahbarhesab.webp') }}">
    <link rel="alternate" hreflang="fa-IR" href="{{ $seoMeta['canonical'] ?? url()->current() }}">
    @php
        $sitePublic = public_path('site');
        $siteVer = fn (string $file) => file_exists($sitePublic . '/' . $file) ? filemtime($sitePublic . '/' . $file) : time();
    @endphp
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preload" as="font" type="font/woff2" crossorigin href="https://cdn.jsdelivr.net/npm/@fontsource-variable/vazirmatn@5.1.0/files/vazirmatn-arabic-wght-normal.woff2">
    <link rel="stylesheet" href="{{ asset('site/fonts.css') }}?v={{ $siteVer('fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('site/style.css') }}?v={{ $siteVer('style.css') }}">
    <link rel="stylesheet" href="{{ asset('site/theme.css') }}?v={{ $siteVer('theme.css') }}">

    @if (!empty($structuredData))
        @foreach ($structuredData as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        @endforeach
    @endif
    @stack('head')
</head>
<body class="rh-theme">
    @if ($gtm = config('seo.analytics.gtm_id'))
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    @include('components.navbar', ['navLinks' => $navLinks ?? []])

    @php $pageSidebars = app(\App\Services\SidebarService::class)->current(); @endphp
    <main @class(['rh-inner-page' => empty($fullBleedContent) && ! request()->routeIs(['home', 'about', 'contact', 'courses.index', 'courses.show', 'blog.*', 'cafe-soal.*', 'laws.*', 'panel.*', 'login', 'login.verify', 'cart.index', 'checkout.index', 'checkout.success', 'checkout.failed', 'search'])])>
        @yield('page-banner')
        @if ($pageSidebars->isNotEmpty())
            <div @class([
                'rh-sidebar-layout',
                'rh-sidebar-layout--under-banner' => $__env->hasSection('page-banner'),
                'rh-sidebar-layout--blog' => request()->routeIs('blog.index'),
                'rh-sidebar-layout--courses' => request()->routeIs('courses.index'),
            ])>
                <div class="rh-sidebar-layout__main">
                    @yield('page')
                </div>
                @include('components.page-sidebar', ['pageSidebars' => $pageSidebars])
            </div>
        @else
            @yield('page')
        @endif
    </main>

    @include('components.footer', ['contact' => $contact ?? []])
    @include('components.site-popup')

    <script src="{{ asset('site/script.js') }}?v={{ $siteVer('script.js') }}"></script>
    @stack('scripts')
</body>
</html>
