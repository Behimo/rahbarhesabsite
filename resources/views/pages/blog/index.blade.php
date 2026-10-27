@extends('layouts.site')

@php
    $pageTitle = $pageTitle ?? 'مقالات حسابداری';
@endphp

@section('page')
<section class="blog-archive">
    <div class="blog-archive__wrap">
        <nav class="courses-catalog-crumb" aria-label="مسیر صفحه">
            <a href="{{ route('home') }}">صفحه اصلی</a>
            <span class="courses-catalog-crumb__sep" aria-hidden="true">/</span>
            <span aria-current="page">{{ $pageTitle }}</span>
        </nav>

        <div class="blog-archive__layout{{ ($sidebars ?? collect())->isEmpty() ? ' is-single' : '' }}">
            <div class="blog-archive__main">
                <h1 class="courses-catalog-title">{{ $pageTitle }}</h1>

                @if ($posts->isEmpty())
                    <div class="blog-archive-empty" role="status">
                        <strong>مطلبی پیدا نشد</strong>
                        <p>به‌زودی مقالات جدید منتشر می‌شود.</p>
                    </div>
                @else
                    <div class="blog-archive__grid">
                        @foreach ($posts as $post)
                            @php
                                $cardImage = $post->featured_image ?: ($post->video_poster ?? null);
                                $cardDate = $post->published_at ?? $post->answered_at;
                            @endphp
                            <article class="blog-archive-card">
                                <a href="{{ $post->url() }}" class="blog-archive-card__hit" aria-label="{{ $post->title }}"></a>
                                <div class="blog-archive-card__media">
                                    @if ($cardImage)
                                        <img src="{{ $cardImage }}" alt="" loading="lazy" decoding="async" width="640" height="360">
                                    @else
                                        <span class="blog-archive-card__fallback" aria-hidden="true"></span>
                                    @endif
                                </div>
                                <h2 class="blog-archive-card__title">{{ $post->title }}</h2>
                                <div class="blog-archive-card__meta">
                                    @if ($cardDate)
                                        <span>
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <rect x="4" y="5" width="16" height="15" rx="2"></rect>
                                                <path d="M8 3v4M16 3v4M4 10h16"></path>
                                            </svg>
                                            {{ fa_ago($cardDate) }}
                                        </span>
                                    @endif
                                    <span>
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M6 16.5V7.5A2.5 2.5 0 0 1 8.5 5h7A2.5 2.5 0 0 1 18 7.5v6A2.5 2.5 0 0 1 15.5 16H9l-3 3v-2.5z"></path>
                                        </svg>
                                        بدون نظرات
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ($posts->hasPages())
                        <div class="courses-catalog-pager blog-archive__pager">
                            {{ $posts->links('pagination.blog', [
                                'paginationBase' => $paginationBase ?? '/blog',
                                'paginationLabel' => 'مطلب',
                            ]) }}
                        </div>
                    @endif
                @endif
            </div>

            @if (($sidebars ?? collect())->isNotEmpty())
                <aside class="blog-archive-side" aria-label="سایدبار">
                    @foreach ($sidebars as $widget)
                        <section class="blog-side-card" aria-labelledby="blog-side-{{ $widget['id'] }}">
                            <h2 id="blog-side-{{ $widget['id'] }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M6 9a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9"></path>
                                    <path d="M10 20a2 2 0 0 0 4 0"></path>
                                </svg>
                                {{ $widget['title'] }}
                            </h2>
                            <ul>
                                @foreach ($widget['items'] as $item)
                                    <li>
                                        @if ($item['url'])
                                            <a href="{{ $item['url'] }}" @class(['blog-side-course', 'has-sale' => ! empty($item['discount'])])>
                                        @else
                                            <div @class(['blog-side-course', 'has-sale' => ! empty($item['discount'])])>
                                        @endif
                                            <span class="blog-side-course__thumb">
                                                @if ($item['image'])
                                                    <img src="{{ $item['image'] }}" alt="" loading="lazy" decoding="async" width="96" height="64">
                                                @else
                                                    <span aria-hidden="true"></span>
                                                @endif
                                            </span>
                                            <span class="blog-side-course__price">
                                                @if (($item['price'] ?? null) === 0)
                                                    <span>رایگان</span>
                                                @elseif (isset($item['price']))
                                                    <span>{{ fa_digits(number_format($item['price'])) }} <small>تومان</small></span>
                                                    @if (! empty($item['regular']))
                                                        <del>{{ fa_digits(number_format($item['regular'])) }}</del>
                                                    @endif
                                                @elseif (! empty($item['meta']))
                                                    <span>{{ $item['meta'] }}</span>
                                                @endif
                                            </span>
                                            @if (! empty($item['discount']))
                                                <b class="blog-side-course__badge">٪{{ fa_digits($item['discount']) }}</b>
                                            @endif
                                            <span class="blog-side-course__title">{{ $item['title'] }}</span>
                                        @if ($item['url'])
                                            </a>
                                        @else
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </aside>
            @endif
        </div>
    </div>
</section>
@endsection
