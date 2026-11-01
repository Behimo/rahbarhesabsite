@push('head')
    @if (!empty($migratedCss))
        @php
            $cssPath = public_path($migratedCss);
            $cssVer = is_file($cssPath) ? filemtime($cssPath) : time();
        @endphp
        <link rel="stylesheet" href="{{ asset($migratedCss) }}?v={{ $cssVer }}">
    @endif
@endpush
