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

    <link rel="icon" href="{{ theme_asset('images/logorahbarhesab.webp') }}">
    <link rel="alternate" hreflang="fa-IR" href="{{ url()->current() }}">
    <link rel="stylesheet" href="{{ theme_asset('style.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('theme.css') }}">

    @if (!empty($structuredData))
        @foreach ($structuredData as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        @endforeach
    @endif
    @stack('head')
</head>
<body class="rh-theme">
    @include('theme::components.navbar', ['navLinks' => $navLinks ?? []])

    <main @unless (request()->routeIs('home')) class="rh-inner-page" @endunless>
        @yield('page')
    </main>

    @include('theme::components.footer', ['contact' => $contact ?? []])

    <script src="{{ theme_asset('script.js') }}"></script>
    @stack('scripts')
</body>
</html>
