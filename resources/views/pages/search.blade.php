@extends('layouts.site')
@section('page')
<div class="mx-auto max-w-5xl px-4 py-10">
    <h1 class="mb-6 text-3xl font-bold">جستجو</h1>
    <form class="mb-8"><input name="q" value="{{ $q }}" class="w-full rounded-lg border px-4 py-2" placeholder="جستجو در سایت..."></form>
    @if($q)
        @if($courses->isNotEmpty())<h2 class="mb-3 text-xl font-semibold">دوره‌ها</h2><ul class="mb-6">@foreach($courses as $c)<li><a href="{{ route('courses.show', $c->slug) }}">{{ $c->title }}</a></li>@endforeach</ul>@endif
        @if($posts->isNotEmpty())<h2 class="mb-3 text-xl font-semibold">بلاگ</h2><ul class="mb-6">@foreach($posts as $p)<li><a href="{{ route('blog.show', $p->slug) }}">{{ $p->title }}</a></li>@endforeach</ul>@endif
        @if($pages->isNotEmpty())<h2 class="mb-3 text-xl font-semibold">صفحات</h2><ul>@foreach($pages as $p)<li><a href="{{ route('pages.show', $p->slug) }}">{{ $p->title }}</a></li>@endforeach</ul>@endif
    @endif
</div>
@endsection
