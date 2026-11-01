@extends('layouts.site')

@include('pages.migrated._assets')

@section('page')
    <div class="page-migrated page-licenses rtl" dir="rtl">
        @include('pages.migrated.html.licenses')
    </div>
@endsection
