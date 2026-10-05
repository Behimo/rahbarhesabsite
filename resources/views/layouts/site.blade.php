<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php $seoMeta = $seo ?? []; @endphp

    <title>{{ $seoMeta['title'] ?? config('cms.site_name_fa') }}</title>
    <meta name="description" content="{{ $seoMeta['description'] ?? '' }}">
    @if (!empty($seoMeta['keywords']))
        <meta name="keywords" content="{{ $seoMeta['keywords'] }}">
    @endif
    <meta name="robots" content="{{ $seoMeta['robots'] ?? 'index, follow' }}">
    <link rel="canonical" href="{{ $seoMeta['canonical'] ?? url()->current() }}">

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

    <link rel="icon" href="{{ asset('site/images/logorahbarhesab.webp') }}">
    <link rel="alternate" hreflang="fa-IR" href="{{ url()->current() }}">
    @php
        $sitePublic = public_path('site');
        $siteVer = fn (string $file) => file_exists($sitePublic . '/' . $file) ? filemtime($sitePublic . '/' . $file) : time();
    @endphp
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="{{ asset('site/fonts.css') }}?v={{ $siteVer('fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('site/style.css') }}?v={{ $siteVer('style.css') }}">
    <link rel="stylesheet" href="{{ asset('site/theme.css') }}?v={{ $siteVer('theme.css') }}">

    @if (!empty($structuredData))
        @foreach ($structuredData as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        @endforeach
    @endif
    @stack('head')
</head>
<body class="rh-theme">
    @include('components.navbar', ['navLinks' => $navLinks ?? []])

    <main @class(['rh-inner-page' => ! request()->routeIs(['home', 'about', 'contact', 'courses.index', 'courses.show', 'blog.*', 'panel.*', 'login', 'login.verify', 'cart.index', 'checkout.index', 'checkout.success', 'checkout.failed', 'search'])])>
        @yield('page')
    </main>

    @include('components.footer', ['contact' => $contact ?? []])
    @include('components.site-popup')

    <script src="{{ asset('site/script.js') }}?v={{ $siteVer('script.js') }}"></script>
    @stack('scripts')
</body>
</html>
