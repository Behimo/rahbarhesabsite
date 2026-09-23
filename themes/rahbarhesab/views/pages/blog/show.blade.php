@extends('theme::layouts.site')

@section('page')
<div class="blog-page blog-page--article">
    <article class="blog-article">
        <div class="blog-article__shell">
            <nav class="blog-breadcrumb" aria-label="مسیر صفحه">
                <a href="{{ route('home') }}">خانه</a>
                <span class="blog-breadcrumb__sep" aria-hidden="true">/</span>
                <a href="{{ route('blog.index') }}">اخبار و مقالات</a>
                @if ($post->category)
                    <span class="blog-breadcrumb__sep" aria-hidden="true">/</span>
                    <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}">{{ $post->category->name }}</a>
                @endif
            </nav>

            <header class="blog-article__header">
                @if ($post->category)
                    <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="blog-tag">{{ $post->category->name }}</a>
                @endif
                <h1 class="blog-article__title">{{ $post->title }}</h1>
                <div class="blog-article__byline">
                    @if ($post->author)
                        <span class="blog-article__author">{{ $post->author }}</span>
                    @endif
                    @if ($post->published_at)
                        <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('Y/m/d') }}</time>
                    @endif
                    <span>{{ number_format($post->views) }} بازدید</span>
                </div>
            </header>

            @if ($post->featured_image)
                <figure class="blog-article__figure">
                    <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" loading="eager" decoding="async">
                </figure>
            @endif

            <div class="blog-article__content prose prose-slate">
                {!! $post->body !!}
            </div>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="blog-related" aria-labelledby="blog-related-heading">
            <div class="blog-shell">
                <div class="blog-related__head">
                    <h2 id="blog-related-heading">مطالب مرتبط</h2>
                    <a href="{{ route('blog.index') }}" class="blog-related__all">همه مقالات</a>
                </div>
                <div class="blog-related__grid">
                    @foreach ($related as $item)
                        <a href="{{ route('blog.show', $item->slug) }}" class="blog-related__card">
                            <div class="blog-related__media">
                                @if ($item->featured_image)
                                    <img src="{{ $item->featured_image }}" alt="" loading="lazy" decoding="async">
                                @else
                                    <div class="blog-card__fallback blog-card__fallback--sm" aria-hidden="true">
                                        <svg viewBox="0 0 40 40" fill="none">
                                            <path d="M10 8h16l6 6v18H10V8z" stroke="currentColor" stroke-width="1.6"/>
                                            <path d="M26 8v6h6M14 20h12M14 25h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <h3>{{ $item->title }}</h3>
                            @if ($item->published_at)
                                <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('Y/m/d') }}</time>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
