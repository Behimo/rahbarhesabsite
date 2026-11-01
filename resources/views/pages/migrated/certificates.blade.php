@extends('layouts.site')

@include('pages.migrated._assets')

@section('page')
    <div class="page-migrated page-certificates rtl" dir="rtl">
        @include('pages.migrated.html.certificates')
    </div>
@endsection
