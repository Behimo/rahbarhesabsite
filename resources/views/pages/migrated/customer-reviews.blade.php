@extends('layouts.site')

@include('pages.migrated._assets')

@section('page')
    <div class="page-migrated page-customer-reviews rtl" dir="rtl">
        @include('pages.migrated.html.customer-reviews')
    </div>
@endsection
