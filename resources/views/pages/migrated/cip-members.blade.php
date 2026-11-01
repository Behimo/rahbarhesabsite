@extends('layouts.site')

@include('pages.migrated._assets')

@section('page')
    <div class="page-migrated page-cip-members rtl" dir="rtl">
        @include('pages.migrated.html.cip-members')
    </div>
@endsection
