@extends('layouts.site')

@include('pages.migrated._assets')

@section('page')
    <div class="page-migrated page-profit-ratios rtl" dir="rtl">
        @include('pages.migrated.html.profit-ratios')
    </div>
@endsection
