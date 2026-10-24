@extends('layouts.site')

@include('pages.partials.scoped-page-assets')

@section('page')
    <div class="wp-imported-page rtl" dir="rtl">
        {!! $bodyHtml ?? '' !!}
    </div>
@endsection
