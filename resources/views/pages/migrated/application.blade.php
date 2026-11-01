@extends('layouts.site')

@include('pages.migrated._assets')

@section('page')
    <div class="page-migrated page-application rtl" dir="rtl">
        @include('pages.migrated.html.application')
    </div>
@endsection
