@extends('layouts.site')

@include('pages.migrated._assets')

@section('page')
    <div class="page-migrated page-terms-and-conditions rtl" dir="rtl">
        @include('pages.migrated.html.terms-and-conditions')
    </div>
@endsection
