@extends('layouts.site')

@section('page-banner')
<div class="laws-hero" aria-labelledby="lawsHeroTitle">
    <div class="laws-hero__atmosphere" aria-hidden="true">
        <span class="laws-hero__glow"></span>
        <span class="laws-hero__beam laws-hero__beam--a"></span>
        <span class="laws-hero__beam laws-hero__beam--b"></span>
        <span class="laws-hero__beam laws-hero__beam--c"></span>
        <svg class="laws-hero__glyph" viewBox="0 0 96 104" fill="none" aria-hidden="true">
            <path d="M28 12h32l16 16v60a8 8 0 0 1-8 8H28a8 8 0 0 1-8-8V20a8 8 0 0 1 8-8z" stroke="currentColor" stroke-width="2.2"/>
            <path d="M60 12v16h16M34 48h28M34 60h28M34 72h18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
        </svg>
    </div>

    <div class="laws-hero__inner">
        <p class="laws-hero__brand">راهبر حساب</p>
        <h1 id="lawsHeroTitle" class="laws-hero__title">مواد قانونی</h1>
        <p class="laws-hero__lead">متن مواد، توضیح ساده، موارد استفاده، مثال و سیر تحول قوانین مرتبط با حسابداری</p>
    </div>
</div>
@endsection

@section('page')
<div class="laws-page">
    <div class="laws-layout{{ $categories->isNotEmpty() ? ' laws-layout--with-side' : '' }}">
        @if ($categories->isNotEmpty())
            <aside class="laws-side" aria-label="دسته‌بندی مواد قانونی">
                <div class="laws-side__card">
                    <h2 class="laws-side__title">دسته‌بندی</h2>
                    <nav class="laws-side__nav">
                        <a href="{{ route('laws.index', array_filter(['q' => $q ?: null, 'sort' => $sort !== 'latest' ? $sort : null])) }}"
                           class="laws-side__link{{ blank($activeCategory) ? ' is-active' : '' }}">
                            همه مواد
                        </a>
                        @foreach ($categories as $category)
                            <a href="{{ route('laws.index', array_filter([
                                    'category' => $category->full_slug,
                                    'q' => $q ?: null,
                                    'sort' => $sort !== 'latest' ? $sort : null,
                                ])) }}"
                               class="laws-side__link{{ $activeCategory === $category->full_slug || $activeCategory === $category->slug ? ' is-active' : '' }}"
                               style="--laws-depth: {{ $category->treeDepth }}">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>
        @endif

        <div class="laws-main">
            <form method="GET" action="{{ route('laws.index') }}" class="laws-search" role="search">
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                @endif
                @if ($sort !== 'latest')
                    <input type="hidden" name="sort" value="{{ $sort }}">
                @endif
                <label class="laws-sr-only" for="laws-q">جستجوی ماده قانونی</label>
                <div class="laws-search__field">
                    <span class="laws-search__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <input type="search" name="q" id="laws-q" value="{{ $q }}" placeholder="جستجو بین مواد قانونی حسابداری..." class="laws-search__input" autocomplete="off">
                </div>
                <button type="submit" class="laws-search__submit">جستجو</button>
            </form>

            <div class="laws-list-head">
                <p class="laws-list-head__count">
                    @if ($laws->total() > 0)
                        {{ fa_digits($laws->total()) }} ماده
                    @else
                        بدون نتیجه
                    @endif
                </p>
                <form method="GET" action="{{ route('laws.index') }}" class="laws-list-head__sort">
                    @if ($q !== '')
                        <input type="hidden" name="q" value="{{ $q }}">
                    @endif
                    @if ($activeCategory)
                        <input type="hidden" name="category" value="{{ $activeCategory }}">
                    @endif
                    <label for="laws-sort">مرتب‌سازی</label>
                    <select name="sort" id="laws-sort" autocomplete="off" onchange="this.form.submit()">
                        <option value="latest" @selected(($sort ?: 'latest') === 'latest')>جدیدترین</option>
                        <option value="popular" @selected($sort === 'popular')>پربازدیدترین</option>
                        <option value="number" @selected($sort === 'number')>شماره ماده</option>
                    </select>
                </form>
            </div>

            @if ($laws->isEmpty())
                <div class="laws-empty" role="status">
                    <div class="laws-empty__icon" aria-hidden="true">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                            <path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M14 3v5h5M8 12h8M8 16h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h2 class="laws-empty__title">
                        @if ($q !== '')
                            نتیجه‌ای برای «{{ $q }}» پیدا نشد
                        @else
                            هنوز ماده‌ای در این فهرست نیست
                        @endif
                    </h2>
                    <p class="laws-empty__text">
                        @if ($q !== '')
                            عبارت دیگری را امتحان کنید یا فیلتر دسته را بردارید.
                        @else
                            به‌زودی مواد قانونی حسابداری با توضیح ساده و سیر تحول منتشر می‌شود.
                        @endif
                    </p>
                    @if ($q !== '' || $activeCategory)
                        <a href="{{ route('laws.index') }}" class="laws-empty__reset">مشاهده همه مواد</a>
                    @endif
                </div>
            @else
                <ul class="laws-list">
                    @foreach ($laws as $index => $law)
                        <li style="--laws-i: {{ $index }}">
                            <a href="{{ route('laws.show', $law->slug) }}" class="laws-item">
                                <span class="laws-item__rail" aria-hidden="true"></span>
                                <span class="laws-item__media" aria-hidden="true">
                                    @if ($law->featured_image)
                                        <img src="{{ $law->featured_image }}" alt="" loading="lazy" decoding="async">
                                    @else
                                        <span class="laws-item__badge">{{ fa_digits($law->article_number) }}</span>
                                    @endif
                                </span>
                                <span class="laws-item__body">
                                    <span class="laws-item__title">
                                        <span class="laws-item__number">ماده {{ fa_digits($law->article_number) }}</span>
                                        {{ $law->title }}
                                    </span>
                                    @if ($law->excerpt())
                                        <span class="laws-item__excerpt">{{ $law->excerpt(140) }}</span>
                                    @endif
                                    <span class="laws-item__meta">
                                        @if ($law->category)
                                            <span class="laws-item__cat">{{ $law->category->name }}</span>
                                        @endif
                                        <span class="laws-item__stat">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7"/></svg>
                                            {{ fa_digits(number_format($law->views)) }} بازدید
                                        </span>
                                        @if ($law->published_at)
                                            <time datetime="{{ $law->published_at->toDateString() }}">{{ fa_date($law->published_at) }}</time>
                                        @endif
                                    </span>
                                </span>
                                <span class="laws-item__arrow" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <path d="M14 6l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="laws-pagination">
                    {{ $laws->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
