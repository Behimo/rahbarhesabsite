@php
    $minutes = (int) ($course->course?->duration_minutes ?? 0);
    if ($minutes >= 60) {
        $durationLabel = fa_digits((int) floor($minutes / 60)).' ساعت';
    } elseif ($minutes > 0) {
        $durationLabel = fa_digits($minutes).' دقیقه';
    } else {
        $durationLabel = null;
    }

    $instructor = $course->course?->instructor?->name;
    $hasDiscount = $course->sale_price && $course->price > $course->sale_price;
    $discountPercent = $hasDiscount
        ? (int) round((1 - ($course->sale_price / $course->price)) * 100)
        : 0;
    $isFreeCard = $isFree ?? $course->isFree();
    $courseUrl = route('courses.show', $course->slug);
    $linked = $linked ?? false;
    $cardClasses = trim(implode(' ', array_filter([
        'course-card',
        $linked ? 'course-card--linked' : null,
        $isFreeCard ? 'course-card--free' : null,
        $hasDiscount ? 'course-card--sale' : null,
    ])));
@endphp

<article class="{{ $cardClasses }}">
    @if ($linked)
        <a href="{{ $courseUrl }}" class="course-card-hit" aria-label="{{ $course->title }}"></a>
    @endif

    @if ($linked)
        <div class="course-image{{ $isFreeCard && ! $course->featured_image ? ' free-course-cover' : '' }}">
    @else
        <a href="{{ $courseUrl }}" class="course-image{{ $isFreeCard && ! $course->featured_image ? ' free-course-cover' : '' }}">
    @endif
        @if ($isFreeCard && ! $course->featured_image)
            <span class="free-course-cover-title">{{ $course->title }}</span>
        @elseif ($course->featured_image)
            <img src="{{ $course->featured_image }}" alt="{{ $course->title }}" loading="lazy" width="320" height="180" />
        @else
            <span class="free-course-cover-title">{{ $course->title }}</span>
        @endif

        @if ($hasDiscount)
            <span class="course-badge course-badge--sale" title="{{ fa_digits($discountPercent) }} درصد تخفیف">
                ٪{{ fa_digits($discountPercent) }}
            </span>
        @elseif ($isFreeCard || $course->isFree())
            <span class="course-badge course-badge--free">رایگان</span>
        @endif
    @if ($linked)
        </div>
    @else
        </a>
    @endif

    <div class="course-content">
        @if ($linked)
            <h3 class="course-title">{{ $course->title }}</h3>
        @else
            <a href="{{ $courseUrl }}" class="course-title">{{ $course->title }}</a>
        @endif

        @if ($instructor || $durationLabel)
            <ul class="course-meta">
                @if ($instructor)
                    <li class="course-meta-item">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3L3 8L12 13L21 8L12 3Z" />
                            <path d="M6 10V15C6 17 8.7 19 12 19C15.3 19 18 17 18 15V10" />
                        </svg>
                        <span>{{ $instructor }}</span>
                    </li>
                @endif

                @if ($durationLabel)
                    <li class="course-meta-item">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="8" />
                            <path d="M12 7V12L15 14" />
                        </svg>
                        <span>{{ $durationLabel }}</span>
                    </li>
                @endif
            </ul>
        @endif

        <div class="course-price{{ $hasDiscount ? ' has-discount' : '' }}{{ $course->isFree() ? ' is-free' : '' }}">
            @if ($course->isFree())
                <span class="current-price free-course-price">رایگان</span>
            @elseif ($hasDiscount)
                <div class="course-price-stack">
                    <div class="course-price-row">
                        <span class="current-price">
                            {{ fa_digits(number_format($course->sale_price)) }}
                            <span class="price-unit">تومان</span>
                        </span>
                        <span class="discount-percent">٪{{ fa_digits($discountPercent) }}</span>
                    </div>
                    <del class="course-price-old">{{ fa_digits(number_format($course->price)) }} تومان</del>
                </div>
            @else
                <span class="current-price">
                    {{ fa_digits(number_format($course->effectivePrice())) }}
                    <span class="price-unit">تومان</span>
                </span>
            @endif
        </div>
    </div>
</article>
