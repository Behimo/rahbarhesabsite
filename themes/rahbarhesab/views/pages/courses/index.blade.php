@extends('theme::layouts.site')

@php
    $activeCategory = request('category');
    $activeType = request('type');
    $searchQuery = request('q');
    $hasFilters = filled($activeCategory) || filled($activeType) || filled($searchQuery);

    $filterUrl = function (array $overrides = []) {
        $query = array_filter([
            'q' => array_key_exists('q', $overrides) ? $overrides['q'] : request('q'),
            'category' => array_key_exists('category', $overrides) ? $overrides['category'] : request('category'),
            'type' => array_key_exists('type', $overrides) ? $overrides['type'] : request('type'),
        ], fn ($value) => filled($value));

        return route('courses.index', $query);
    };

    $activeCategoryName = $categories->firstWhere('slug', $activeCategory)?->name;
    $typeLabels = [
        'paid' => 'دوره‌های تخصصی',
        'free' => 'رایگان',
    ];
@endphp

@section('page')
<section class="courses-archive">
    <div class="courses-archive-hero">
        <div class="courses-archive-hero-glow" aria-hidden="true"></div>
        <div class="courses-archive-hero-dots" aria-hidden="true"></div>

        <div class="courses-archive-hero-inner">
            <p class="courses-archive-kicker">آموزش حسابداری ویژه بازار کار</p>
            <h1>دوره‌های آموزشی حسابداری</h1>
            <p class="courses-archive-lead">آموزش عملی حسابداری و مالیات با مدرسین بازار کار</p>

            <form class="courses-archive-search" action="{{ route('courses.index') }}" method="get" role="search">
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                @endif
                @if ($activeType)
                    <input type="hidden" name="type" value="{{ $activeType }}">
                @endif

                <label class="courses-archive-search-field">
                    <svg class="courses-archive-search-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path d="M16.5 16.5L21 21" />
                    </svg>
                    <input
                        type="search"
                        name="q"
                        value="{{ $searchQuery }}"
                        placeholder="جستجو در دوره‌ها..."
                        aria-label="جستجوی دوره"
                        autocomplete="off"
                    >
                    @if ($searchQuery)
                        <a href="{{ $filterUrl(['q' => null]) }}" class="courses-archive-search-clear" aria-label="پاک کردن جستجو">&times;</a>
                    @endif
                </label>

                <button type="submit">جستجو</button>
            </form>

            @if ($categories->isNotEmpty())
                <div class="courses-archive-chips-wrap">
                    <span class="courses-archive-chips-label">دسته‌بندی:</span>
                    <div class="courses-archive-chips" role="navigation" aria-label="دسته‌بندی دوره‌ها">
                        <a href="{{ $filterUrl(['category' => null]) }}" class="{{ blank($activeCategory) ? 'is-active' : '' }}">همه</a>
                        @foreach ($categories as $category)
                            <a href="{{ $filterUrl(['category' => $category->slug]) }}" class="{{ $activeCategory === $category->slug ? 'is-active' : '' }}">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="courses-archive-body">
        <div class="courses-archive-toolbar">
            <div class="courses-archive-toolbar-start">
                <p class="courses-archive-count">
                    <span class="courses-archive-count-number">{{ fa_digits($courses->count()) }}</span>
                    {{ $hasFilters ? 'نتیجه' : 'دوره آموزشی' }}
                </p>

                @if ($hasFilters)
                    <div class="courses-archive-active-filters" aria-label="فیلترهای فعال">
                        @if ($searchQuery)
                            <a href="{{ $filterUrl(['q' => null]) }}" class="courses-archive-filter-tag">
                                «{{ Str::limit($searchQuery, 24) }}»
                                <span aria-hidden="true">&times;</span>
                            </a>
                        @endif
                        @if ($activeCategoryName)
                            <a href="{{ $filterUrl(['category' => null]) }}" class="courses-archive-filter-tag">
                                {{ $activeCategoryName }}
                                <span aria-hidden="true">&times;</span>
                            </a>
                        @endif
                        @if ($activeType && isset($typeLabels[$activeType]))
                            <a href="{{ $filterUrl(['type' => null]) }}" class="courses-archive-filter-tag">
                                {{ $typeLabels[$activeType] }}
                                <span aria-hidden="true">&times;</span>
                            </a>
                        @endif
                        <a href="{{ route('courses.index') }}" class="courses-archive-clear-all">حذف همه</a>
                    </div>
                @endif
            </div>

            <div class="courses-archive-types-wrap">
                <span class="courses-archive-types-label">نوع:</span>
                <div class="courses-archive-types" role="group" aria-label="نوع دوره">
                    <a href="{{ $filterUrl(['type' => null]) }}" class="{{ blank($activeType) ? 'is-active' : '' }}">همه</a>
                    <a href="{{ $filterUrl(['type' => 'paid']) }}" class="{{ $activeType === 'paid' ? 'is-active' : '' }}">تخصصی</a>
                    <a href="{{ $filterUrl(['type' => 'free']) }}" class="{{ $activeType === 'free' ? 'is-active' : '' }}">رایگان</a>
                </div>
            </div>
        </div>

        @if ($courses->isEmpty())
            <div class="courses-archive-empty">
                <div class="courses-archive-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7" />
                        <path d="M16.5 16.5L21 21" />
                    </svg>
                </div>
                <strong>دوره‌ای با این مشخصات پیدا نشد</strong>
                <p>عبارت جستجو یا فیلتر را تغییر دهید.</p>
                @if ($hasFilters)
                    <a href="{{ route('courses.index') }}" class="courses-archive-empty-btn">مشاهده همه دوره‌ها</a>
                @endif
            </div>
        @else
            <div class="courses-archive-grid">
                @foreach ($courses as $course)
                    @include('theme::partials.course-card', [
                        'course' => $course,
                        'isFree' => $course->isFree(),
                        'linked' => true,
                    ])
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
