@extends('layouts.site')

@include('pages.migrated._assets')

@section('page')
    <div class="page-migrated page-software rtl" dir="rtl">
        @include('pages.migrated.html.software')
    </div>
@endsection
