@extends('theme::layouts.site')

@section('page')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    @if(!empty($page?->title))
        <h1 class="mb-8 text-3xl font-extrabold text-slate-800">{{ $page->title }}</h1>
    @endif
    <div class="cms-page-content prose max-w-none">
        {!! $bodyHtml ?? '' !!}
    </div>
</div>
@endsection
