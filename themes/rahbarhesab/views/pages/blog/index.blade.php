@extends('theme::layouts.site')

@section('page')
@php
    $listing = $posts->getCollection();
    if ($featured) {
        $listing = $listing->reject(fn ($post) => $post->id === $featured->id)->values();
    }
@endphp

<div class="blog-page">
    <header class="blog-masthead">
        <div class="blog-masthead__ledger" aria-hidden="true"></div>
        <div class="blog-masthead__inner">
            <p class="blog-masthead__brand">راهبر حساب</p>
            <h1 class="blog-masthead__title">اخبار و مقالات حسابداری</h1>
            <p class="blog-masthead__lead">بخشنامه‌ها، راهنماهای مالیاتی و تحلیل‌های کاربردی برای حسابداران و مدیران مالی</p>
        </div>
    </header>

    <div class="blog-shell">
        @if ($categories->isNotEmpty())
            <nav class="blog-filters" aria-label="دسته‌بندی مطالب">
                <a href="{{ route('blog.index') }}"
                   class="blog-filter{{ blank($activeCategory) ? ' is-active' : '' }}">
                    همه مطالب
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('blog.index', ['category' => $category->slug]) }}"
                       class="blog-filter{{ $activeCategory === $category->slug ? ' is-active' : '' }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </nav>
        @endif

        @if ($posts->isEmpty())
            <div class="blog-empty" role="status">
                <svg class="blog-empty__icon" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                    <rect x="8" y="6" width="32" height="36" rx="3" stroke="currentColor" stroke-width="2"/>
                    <path d="M16 16h16M16 24h16M16 32h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <p>به‌زودی مقالات و بخشنامه‌های جدید منتشر می‌شود.</p>
            </div>
        @else
            @if ($featured)
                <section class="blog-featured" aria-label="مطالب ویژه">
                    <a href="{{ route('blog.show', $featured->slug) }}" class="blog-featured__card">
                        <div class="blog-featured__media">
                            @if ($featured->featured_image)
                                <img src="{{ $featured->featured_image }}" alt="" loading="eager" decoding="async">
                            @else
                                <div class="blog-featured__fallback" aria-hidden="true">
                                    <span>{{ mb_substr($featured->title, 0, 1) }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="blog-featured__body">
                            <div class="blog-featured__meta">
                                @if ($featured->category)
                                    <span class="blog-tag">{{ $featured->category->name }}</span>
                                @endif
                                @if ($featured->published_at)
                                    <time datetime="{{ $featured->published_at->toDateString() }}">{{ $featured->published_at->format('Y/m/d') }}</time>
                                @endif
                            </div>
                            <h2 class="blog-featured__title">{{ $featured->title }}</h2>
                            @if ($featured->excerpt)
                                <p class="blog-featured__excerpt">{{ $featured->excerpt }}</p>
                            @endif
                            <span class="blog-featured__cta">
                                ادامه مطلب
                                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M12.5 4.5L7 10l5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                    </a>
                </section>
            @endif

            @if ($listing->isNotEmpty())
                <section class="blog-grid" aria-label="فهرست مطالب">
                    @foreach ($listing as $post)
                        <article class="blog-card">
                            <a href="{{ route('blog.show', $post->slug) }}" class="blog-card__link">
                                <div class="blog-card__media">
                                    @if ($post->featured_image)
                                        <img src="{{ $post->featured_image }}" alt="" loading="lazy" decoding="async">
                                    @else
                                        <div class="blog-card__fallback" aria-hidden="true">
                                            <svg viewBox="0 0 40 40" fill="none">
                                                <path d="M10 8h16l6 6v18H10V8z" stroke="currentColor" stroke-width="1.6"/>
                                                <path d="M26 8v6h6M14 20h12M14 25h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="blog-card__body">
                                    <div class="blog-card__meta">
                                        @if ($post->category)
                                            <span class="blog-tag blog-tag--quiet">{{ $post->category->name }}</span>
                                        @endif
                                        @if ($post->published_at)
                                            <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('Y/m/d') }}</time>
                                        @endif
                                    </div>
                                    <h2 class="blog-card__title">{{ $post->title }}</h2>
                                    @if ($post->excerpt)
                                        <p class="blog-card__excerpt">{{ $post->excerpt }}</p>
                                    @endif
                                </div>
                            </a>
                        </article>
                    @endforeach
                </section>
            @endif

            @if ($posts->hasPages())
                <div class="blog-pagination">
                    {{ $posts->links('theme::pagination.blog') }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
