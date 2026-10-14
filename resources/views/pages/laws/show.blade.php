@extends('layouts.site')

@section('page')
<div class="blog-page blog-page--article">
    @if (!empty($isPreview))
        <div class="blog-shell" style="padding-top:16px;">
            <div class="alert alert-warning mb-0" role="status">
                حالت پیش‌نمایش — این ماده ممکن است هنوز منتشر نشده باشد.
            </div>
        </div>
    @endif

    <article class="blog-article">
        <div class="blog-article__shell">
            <nav class="blog-breadcrumb" aria-label="مسیر صفحه">
                <a href="{{ route('home') }}">خانه</a>
                <span class="blog-breadcrumb__sep" aria-hidden="true">/</span>
                <a href="{{ route('laws.index') }}">مواد قانونی</a>
                @if ($law->category)
                    @foreach ($law->category->ancestors as $ancestor)
                        <span class="blog-breadcrumb__sep" aria-hidden="true">/</span>
                        <a href="{{ route('laws.index', ['category' => $ancestor->full_slug]) }}">{{ $ancestor->name }}</a>
                    @endforeach
                    <span class="blog-breadcrumb__sep" aria-hidden="true">/</span>
                    <a href="{{ route('laws.index', ['category' => $law->category->full_slug]) }}">{{ $law->category->name }}</a>
                @endif
            </nav>

            <header class="blog-article__header">
                @if ($law->category)
                    <a href="{{ route('laws.index', ['category' => $law->category->full_slug]) }}" class="blog-tag">{{ $law->category->name }}</a>
                @endif
                <p class="law-article__number">ماده {{ fa_digits($law->article_number) }}</p>
                <h1 class="blog-article__title">{{ $law->title }}</h1>
                <div class="blog-article__byline">
                    @if ($law->published_at)
                        <time datetime="{{ $law->published_at->toDateString() }}">انتشار {{ fa_date($law->published_at) }}</time>
                    @endif
                    <span>{{ fa_digits(number_format($law->views)) }} بازدید</span>
                </div>
            </header>

            @if ($law->featured_image)
                <figure class="blog-article__figure">
                    <img src="{{ $law->featured_image }}" alt="{{ $law->featured_image_alt ?: $law->display_title }}" loading="eager" decoding="async">
                </figure>
            @endif

            @php
                $sections = [
                    'introduction' => 'مقدمه',
                    'article_text' => 'متن ماده',
                    'use_cases' => 'موارد استفاده',
                    'plain_language' => 'به زبان ساده',
                ];
            @endphp

            @foreach ($sections as $field => $label)
                @if (filled($law->{$field}))
                    <section class="law-section" aria-labelledby="law-section-{{ $field }}">
                        <h2 id="law-section-{{ $field }}" class="law-section__title">{{ $label }}</h2>
                        <div class="blog-article__content prose prose-slate">
                            {!! clean_html($law->{$field}) !!}
                        </div>
                    </section>
                @endif
            @endforeach

            @if ($law->evolutions->isNotEmpty())
                <section class="law-section" aria-labelledby="law-section-evolution">
                    <h2 id="law-section-evolution" class="law-section__title">سیر تحول</h2>
                    <ol class="law-timeline">
                        @foreach ($law->evolutions as $evolution)
                            <li class="law-timeline__item">
                                <div class="law-timeline__marker" aria-hidden="true"></div>
                                <div class="law-timeline__body">
                                    <time class="law-timeline__date" dir="ltr">{{ $evolution->event_date }}</time>
                                    <h3 class="law-timeline__title">{{ $evolution->title }}</h3>
                                    @if (filled($evolution->description))
                                        <p class="law-timeline__desc">{{ $evolution->description }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @foreach ([
                'example' => 'مثال',
                'conclusion' => 'نتیجه‌گیری',
            ] as $field => $label)
                @if (filled($law->{$field}))
                    <section class="law-section" aria-labelledby="law-section-{{ $field }}">
                        <h2 id="law-section-{{ $field }}" class="law-section__title">{{ $label }}</h2>
                        <div class="blog-article__content prose prose-slate">
                            {!! clean_html($law->{$field}) !!}
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="law-related" aria-labelledby="law-related-heading">
            <div class="blog-shell">
                <div class="law-related__head">
                    <h2 id="law-related-heading">مواد مرتبط</h2>
                    <a href="{{ route('laws.index') }}" class="law-related__all">همه مواد</a>
                </div>
                <div class="law-related__grid">
                    @foreach ($related as $item)
                        <a href="{{ route('laws.show', $item->slug) }}" class="law-related__card">
                            @if ($item->featured_image)
                                <div class="law-related__media">
                                    <img src="{{ $item->featured_image }}" alt="{{ $item->featured_image_alt ?: $item->display_title }}" loading="lazy" decoding="async">
                                </div>
                            @endif
                            <div class="law-related__body">
                                <div class="law-related__meta">
                                    <span class="law-related__number">ماده {{ fa_digits($item->article_number) }}</span>
                                    @if ($item->category)
                                        <span class="blog-tag blog-tag--quiet">{{ $item->category->name }}</span>
                                    @endif
                                </div>
                                <h3>{{ $item->title }}</h3>
                                @if ($item->published_at)
                                    <time datetime="{{ $item->published_at->toDateString() }}">{{ fa_date($item->published_at) }}</time>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
