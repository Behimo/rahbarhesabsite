@extends('layouts.site')

@push('head')
    @foreach ($stylesheets ?? [] as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    <style>
        .wp-imported-page { width: 100%; }
        .wp-imported-page .elementor-location-header,
        .wp-imported-page .elementor-location-footer { display: none !important; }
    </style>
@endpush

@section('page')
    <div class="wp-imported-page">
        {!! $bodyHtml ?? '' !!}
    </div>
@endsection
