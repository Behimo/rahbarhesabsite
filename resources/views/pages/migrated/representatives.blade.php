@extends('layouts.site')

@include('pages.migrated._assets')

@section('page')
    <div class="page-migrated page-representatives rtl" dir="rtl">
        @include('pages.migrated.html.representatives')
    </div>
@endsection
