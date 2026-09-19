@extends('theme::layouts.site')

@section('page')
@if(!empty($page?->title))
<section class="bg-teal-800 py-10 text-white">
    <div class="mx-auto max-w-5xl px-4 text-center sm:px-6">
        <h1 class="text-3xl font-extrabold">{{ $page->title }}</h1>
    </div>
</section>
@endif
{!! $bodyHtml ?? '' !!}
@endsection
