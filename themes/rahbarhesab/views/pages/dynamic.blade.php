@extends('theme::layouts.site')

@section('page')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <nav class="mb-6 text-sm text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-teal-700">خانه</a>
        <span class="mx-2">/</span>
        <span>{{ $page->title }}</span>
    </nav>

    <div class="rh-card p-6 sm:p-8">
        <h1 class="mb-6 text-3xl font-extrabold text-slate-800">{{ $page->title }}</h1>
        <div class="prose prose-slate max-w-none">{!! $bodyHtml ?? '' !!}</div>
    </div>
</div>
@endsection
