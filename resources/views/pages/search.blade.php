@extends('layouts.site')

@section('page')
@php
    $hasQuery = $q !== '';
    $total = $courses->count() + $posts->count() + $pages->count();

    $mark = function (string $text) use ($q): string {
        $escaped = e($text);
        $needle = trim($q);

        if ($needle === '') {
            return $escaped;
        }

        $word = preg_quote($needle, '/');

        return preg_replace(
            '/(?<![\x{0600}-\x{06FF}])('.$word.'[\x{0600}-\x{06FF}]*)/iu',
            '<mark class="site-search__mark">$1</mark>',
            $escaped
        ) ?? $escaped;
    };
@endphp

<div class="site-search">
    <header class="site-search__mast">
        <div class="site-search__rules" aria-hidden="true"></div>
        <div class="site-search__mast-inner">
            <h1>جستجو</h1>
            <p>دوره، مقاله یا صفحه را با نامش پیدا کنید.</p>
        </div>
    </header>

    <div class="site-search__sheet">
        <form class="site-search__form" action="{{ route('search') }}" method="get" role="search">
            <label class="site-search__label" for="site-search-q">عبارت جستجو</label>
            <div class="site-search__bar">
                <div class="site-search__field">
                    <svg class="site-search__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <input
                        id="site-search-q"
                        type="search"
                        name="q"
                        value="{{ $q }}"
                        placeholder="مثلاً مالیات بر ارزش افزوده"
                        autocomplete="off"
                        enterkeyhint="search"
                        dir="auto"
                        @if (! $hasQuery) autofocus @endif
                    >
                    @if ($hasQuery)
                        <a href="{{ route('search') }}" class="site-search__clear">پاک کردن</a>
                    @endif
                </div>
                <button type="submit" class="site-search__submit">جستجو</button>
            </div>
        </form>

        @if (! $hasQuery)
            <div class="site-search__empty" role="status">
                <p>عبارتی بنویسید تا در دوره‌ها، مقالات و صفحات بگردیم.</p>
                <div class="site-search__jumps">
                    <a href="{{ route('courses.index') }}">دوره‌های آموزشی</a>
                    <a href="{{ route('blog.index') }}">اخبار و مقالات</a>
                </div>
            </div>
        @elseif ($total === 0)
            <div class="site-search__empty" role="status">
                <p>برای «<bdi>{{ $q }}</bdi>» نتیجه‌ای نیست. عبارت کوتاه‌تری امتحان کنید، یا از این‌جا شروع کنید.</p>
                <div class="site-search__jumps">
                    <a href="{{ route('courses.index') }}">دوره‌های آموزشی</a>
                    <a href="{{ route('blog.index') }}">اخبار و مقالات</a>
                </div>
            </div>
        @else
            <p class="site-search__count" role="status">
                <strong>{{ fa_digits($total) }}</strong>
                نتیجه برای «<bdi>{{ $q }}</bdi>»
            </p>

            @if ($courses->isNotEmpty())
                <section class="site-search__group" aria-labelledby="search-courses">
                    <h2 id="search-courses">
                        دوره‌ها
                        <span>{{ fa_digits($courses->count()) }}</span>
                    </h2>
                    <div class="site-search__list">
                        @foreach ($courses as $course)
                            <a href="{{ route('courses.show', $course->slug) }}" class="site-search__row">
                                <span class="site-search__thumb">
                                    @if ($course->featured_image)
                                        <img src="{{ $course->featured_image }}" alt="" loading="lazy" decoding="async" width="72" height="72">
                                    @else
                                        <span aria-hidden="true">{{ mb_substr($course->title, 0, 1) }}</span>
                                    @endif
                                </span>
                                <span class="site-search__copy">
                                    <span class="site-search__name">{!! $mark($course->title) !!}</span>
                                    @if ($course->subtitle)
                                        <span class="site-search__hint">{{ $course->subtitle }}</span>
                                    @endif
                                </span>
                                <span class="site-search__aside{{ $course->isFree() ? ' is-free' : '' }}">
                                    @if ($course->isFree())
                                        رایگان
                                    @else
                                        {{ fa_digits(number_format($course->effectivePrice())) }} تومان
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($posts->isNotEmpty())
                <section class="site-search__group" aria-labelledby="search-posts">
                    <h2 id="search-posts">
                        مقالات
                        <span>{{ fa_digits($posts->count()) }}</span>
                    </h2>
                    <div class="site-search__list">
                        @foreach ($posts as $post)
                            <a href="{{ route('blog.show', $post->slug) }}" class="site-search__row">
                                <span class="site-search__thumb">
                                    @if ($post->featured_image)
                                        <img src="{{ $post->featured_image }}" alt="" loading="lazy" decoding="async" width="72" height="72">
                                    @else
                                        <svg viewBox="0 0 40 40" fill="none" aria-hidden="true">
                                            <path d="M10 8h16l6 6v18H10V8z" stroke="currentColor" stroke-width="1.6"/>
                                            <path d="M26 8v6h6M14 20h12M14 25h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                        </svg>
                                    @endif
                                </span>
                                <span class="site-search__copy">
                                    <span class="site-search__name">{!! $mark($post->title) !!}</span>
                                    @if ($post->excerpt)
                                        <span class="site-search__hint">{{ Str::limit(trim(strip_tags($post->excerpt)), 120) }}</span>
                                    @endif
                                </span>
                                @if ($post->published_at)
                                    <time class="site-search__aside" datetime="{{ $post->published_at->toDateString() }}">{{ fa_date($post->published_at) }}</time>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($pages->isNotEmpty())
                <section class="site-search__group" aria-labelledby="search-pages">
                    <h2 id="search-pages">
                        صفحات
                        <span>{{ fa_digits($pages->count()) }}</span>
                    </h2>
                    <div class="site-search__list">
                        @foreach ($pages as $page)
                            <a href="{{ route('pages.show', $page->slug) }}" class="site-search__row site-search__row--page">
                                <span class="site-search__thumb" aria-hidden="true">
                                    <svg viewBox="0 0 40 40" fill="none">
                                        <path d="M12 8h12l6 6v18H12V8z" stroke="currentColor" stroke-width="1.6"/>
                                        <path d="M24 8v6h6" stroke="currentColor" stroke-width="1.6"/>
                                    </svg>
                                </span>
                                <span class="site-search__copy">
                                    <span class="site-search__name">{!! $mark($page->title) !!}</span>
                                    @if ($page->meta_description)
                                        <span class="site-search__hint">{{ Str::limit(trim(strip_tags($page->meta_description)), 120) }}</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
</div>
@endsection
