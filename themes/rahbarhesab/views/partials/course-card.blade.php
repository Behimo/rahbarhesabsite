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
@endphp

<article class="course-card">
    <a href="{{ route('courses.show', $course->slug) }}" class="course-image{{ $isFreeCard ? ' free-course-cover' : '' }}">
        @if ($isFreeCard && ! $course->featured_image)
            <span>{{ $course->title }}</span>
        @elseif ($course->featured_image)
            <img src="{{ $course->featured_image }}" alt="{{ $course->title }}" />
        @else
            <span>{{ $course->title }}</span>
        @endif
    </a>

    <div class="course-content">
        <a href="{{ route('courses.show', $course->slug) }}" class="course-title">{{ $course->title }}</a>

        <div class="course-info">
            @if ($instructor)
                <div class="course-info-item">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3L3 8L12 13L21 8L12 3Z" />
                        <path d="M6 10V15C6 17 8.7 19 12 19C15.3 19 18 17 18 15V10" />
                    </svg>
                    <span>مدرس : {{ $instructor }}</span>
                </div>
            @endif

            @if ($durationLabel)
                <div class="course-info-item">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="8" />
                        <path d="M12 7V12L15 14" />
                    </svg>
                    <span>{{ $durationLabel }}</span>
                </div>
            @endif
        </div>

        <div class="course-price{{ $hasDiscount ? ' has-discount' : '' }}">
            @if ($course->isFree())
                <span class="current-price free-course-price">رایگان</span>
            @elseif ($hasDiscount)
                <div class="price-values">
                    <del>{{ fa_digits(number_format($course->price)) }} تومان</del>
                    <span class="current-price">{{ fa_digits(number_format($course->sale_price)) }} تومان</span>
                </div>
                <span class="discount-badge">٪{{ fa_digits($discountPercent) }}</span>
            @else
                <span class="current-price">{{ fa_digits(number_format($course->effectivePrice())) }} تومان</span>
            @endif
        </div>
    </div>
</article>
