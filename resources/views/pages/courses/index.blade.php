@extends('layouts.site')

@php
    $activeCategory = $activeCategory ?? request('category');
    $activeType = request('type');
    $searchQuery = request('q');
    $hasFilters = filled($activeCategory) || filled($activeType) || filled($searchQuery);

    $filterUrl = function (array $overrides = []) {
        $category = array_key_exists('category', $overrides) ? $overrides['category'] : request('category');
        $query = array_filter([
            'q' => array_key_exists('q', $overrides) ? $overrides['q'] : request('q'),
            'type' => array_key_exists('type', $overrides) ? $overrides['type'] : request('type'),
        ], fn ($value) => filled($value));

        if (filled($category)) {
            return route('courses.category', array_merge(['category' => $category], $query));
        }

        return route('courses.index', $query);
    };

    $activeCategoryName = $categories->first(fn ($category) => $category->full_slug === $activeCategory || $category->slug === $activeCategory)?->name;
    $pageTitle = $activeCategoryName ?: 'دوره‌های آموزشی حسابداری';
    $typeLabels = [
        'paid' => 'دوره‌های تخصصی',
        'free' => 'رایگان',
    ];
@endphp

@section('page')
<section class="courses-archive">
    <div class="courses-catalog">
        <nav class="courses-catalog-crumb" aria-label="مسیر صفحه">
            <a href="{{ route('home') }}">صفحه اصلی</a>
            <span class="courses-catalog-crumb__sep" aria-hidden="true">/</span>
            @if ($activeCategoryName)
                <a href="{{ route('courses.index') }}">دوره‌های آموزشی حسابداری</a>
                <span class="courses-catalog-crumb__sep" aria-hidden="true">/</span>
                <span aria-current="page">{{ $activeCategoryName }}</span>
            @else
                <span aria-current="page">دوره‌های آموزشی حسابداری</span>
            @endif
        </nav>

        <h1 class="courses-catalog-title">{{ $pageTitle }}</h1>

        <div class="courses-catalog-layout">
            <div class="courses-catalog-main">
                @if ($hasFilters)
                    <div class="courses-archive-active-filters" aria-label="فیلترهای فعال">
                        @if ($searchQuery)
                            <a href="{{ $filterUrl(['q' => null]) }}" class="courses-archive-filter-tag">
                                «{{ Str::limit($searchQuery, 24) }}»
                                <span aria-hidden="true">×</span>
                                <span class="courses-sr-only">حذف جستجو</span>
                            </a>
                        @endif
                        @if ($activeCategoryName)
                            <a href="{{ $filterUrl(['category' => null]) }}" class="courses-archive-filter-tag">
                                {{ $activeCategoryName }}
                                <span aria-hidden="true">×</span>
                                <span class="courses-sr-only">حذف دسته</span>
                            </a>
                        @endif
                        @if ($activeType && isset($typeLabels[$activeType]))
                            <a href="{{ $filterUrl(['type' => null]) }}" class="courses-archive-filter-tag">
                                {{ $typeLabels[$activeType] }}
                                <span aria-hidden="true">×</span>
                                <span class="courses-sr-only">حذف نوع</span>
                            </a>
                        @endif
                        <a href="{{ route('courses.index') }}" class="courses-archive-clear-all">حذف همه</a>
                    </div>
                @endif

                @if ($courses->isEmpty())
                    <div class="courses-archive-empty">
                        <strong>دوره‌ای با این مشخصات پیدا نشد</strong>
                        <p>عبارت جستجو یا فیلتر را تغییر دهید.</p>
                        @if ($hasFilters)
                            <a href="{{ route('courses.index') }}" class="courses-archive-empty-btn">مشاهده همه دوره‌ها</a>
                        @endif
                    </div>
                @else
                    <div class="courses-archive-grid">
                        @foreach ($courses as $course)
                            @include('partials.course-card', [
                                'course' => $course,
                                'isFree' => $course->isFree(),
                                'linked' => true,
                                'catalog' => true,
                            ])
                        @endforeach
                    </div>

                    @if (method_exists($courses, 'hasPages') && $courses->hasPages())
                        <div class="courses-catalog-pager">
                            {{ $courses->links('pagination.blog', [
                                'paginationBase' => filled($activeCategory) ? '/product-category/'.$activeCategory : '/product',
                                'paginationLabel' => 'دوره',
                            ]) }}
                        </div>
                    @endif
                @endif
            </div>

            <aside class="courses-catalog-side" aria-labelledby="courses-filter-title">
                <h2 id="courses-filter-title">فیلتر دوره‌ها</h2>

                <form class="courses-catalog-search" action="{{ route('courses.index') }}" method="get" role="search">
                    @if ($activeCategory)
                        <input type="hidden" name="category" value="{{ $activeCategory }}">
                    @endif
                    @if ($activeType)
                        <input type="hidden" name="type" value="{{ $activeType }}">
                    @endif

                    <label class="courses-catalog-search__label" for="courses-catalog-q">جستجو در دوره‌ها</label>
                    <div class="courses-catalog-search__row">
                        <input
                            id="courses-catalog-q"
                            type="search"
                            name="q"
                            value="{{ $searchQuery }}"
                            placeholder="نام دوره"
                            autocomplete="off"
                        >
                        <button type="submit">جستجو</button>
                    </div>
                </form>

                <p class="courses-catalog-side__label" id="courses-type-label">نوع دوره</p>
                <div class="courses-catalog-types" role="group" aria-labelledby="courses-type-label">
                    <a href="{{ $filterUrl(['type' => null]) }}" class="{{ blank($activeType) ? 'is-active' : '' }}">همه</a>
                    <a href="{{ $filterUrl(['type' => 'paid']) }}" class="{{ $activeType === 'paid' ? 'is-active' : '' }}">تخصصی</a>
                    <a href="{{ $filterUrl(['type' => 'free']) }}" class="{{ $activeType === 'free' ? 'is-active' : '' }}">رایگان</a>
                </div>

                <p class="courses-catalog-side__label" id="courses-cat-label">دسته‌بندی</p>
                <ul class="courses-catalog-cats" aria-labelledby="courses-cat-label">
                    <li>
                        <a href="{{ $filterUrl(['category' => null]) }}" class="{{ blank($activeCategory) ? 'is-active' : '' }}" @if (blank($activeCategory)) aria-current="page" @endif>همه دوره‌ها</a>
                    </li>
                    @foreach ($categories as $category)
                        @php
                            $isCurrentCategory = $activeCategory === $category->full_slug || $activeCategory === $category->slug;
                        @endphp
                        <li>
                            <a
                                href="{{ $filterUrl(['category' => $category->full_slug]) }}"
                                class="{{ $isCurrentCategory ? 'is-active' : '' }}"
                                style="--depth: {{ (int) $category->treeDepth }}"
                                @if ($isCurrentCategory) aria-current="page" @endif
                            >{{ $category->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </aside>
        </div>
    </div>
</section>
@endsection
