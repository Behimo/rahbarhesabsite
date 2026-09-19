@extends('theme::layouts.site')

@section('page')
<article class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <nav class="mb-6 text-sm text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-teal-700">خانه</a>
        <span class="mx-2">/</span>
        <a href="{{ route('blog.index') }}" class="hover:text-teal-700">بلاگ</a>
        <span class="mx-2">/</span>
        <span>{{ $post->title }}</span>
    </nav>

    <div class="rh-card p-6 sm:p-8">
        @if ($post->category)
            <span class="text-sm font-semibold text-teal-700">{{ $post->category->name }}</span>
        @endif
        <h1 class="mt-2 mb-4 text-3xl font-extrabold text-slate-800">{{ $post->title }}</h1>
        <div class="mb-6 flex flex-wrap gap-4 border-b border-slate-200 pb-4 text-sm text-slate-500">
            @if ($post->author)<span>{{ $post->author }}</span>@endif
            @if ($post->published_at)<time>{{ $post->published_at->format('Y/m/d') }}</time>@endif
            <span>{{ number_format($post->views) }} بازدید</span>
        </div>
        @if ($post->featured_image)
            <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="mb-8 w-full rounded-xl">
        @endif
        <div class="prose prose-slate max-w-none leading-8">{!! $post->body !!}</div>
    </div>

    @if ($related->isNotEmpty())
        <section class="mt-12">
            <h2 class="mb-4 text-xl font-bold text-slate-800">مطالب مرتبط</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ($related as $item)
                    <a href="{{ route('blog.show', $item->slug) }}" class="rh-card block p-4 hover:shadow-md">
                        <h3 class="font-semibold text-slate-800">{{ $item->title }}</h3>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</article>
@endsection
