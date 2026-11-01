@extends('layouts.site')

@include('pages.migrated._assets')

@section('page')
    <div class="page-migrated page-terms rtl" dir="rtl">
        @include('pages.migrated.html.terms')
    </div>
@endsection
