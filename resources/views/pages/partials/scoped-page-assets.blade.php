@push('head')
    @if (!empty($scopedStylesheet))
        <link rel="stylesheet" href="{{ $scopedStylesheet }}">
    @endif
    @if (!empty($scopedCss))
        <style>{!! $scopedCss !!}</style>
    @endif
    <style>
        .wp-imported-page {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow-x: clip;
        }
        .wp-imported-page .elementor-location-header,
        .wp-imported-page .elementor-location-footer { display: none !important; }
    </style>
@endpush
