@extends('theme::layouts.site')

@php
    $lessonsCount = $course->sections->sum(fn ($s) => $s->lessons->count());
    $sectionsCount = $course->sections->count();
    $minutes = (int) ($course->duration_minutes ?? 0);
    if ($minutes >= 60) {
        $hours = (int) floor($minutes / 60);
        $remain = $minutes % 60;
        $durationLabel = fa_digits($hours).' ساعت'.($remain > 0 ? ' و '.fa_digits($remain).' دقیقه' : '');
    } elseif ($minutes > 0) {
        $durationLabel = fa_digits($minutes).' دقیقه';
    } else {
        $durationLabel = null;
    }

    $levelLabels = [
        'beginner' => 'مقدماتی',
        'intermediate' => 'متوسط',
        'advanced' => 'پیشرفته',
    ];
    $levelLabel = $levelLabels[$course->level ?? ''] ?? null;

    $hasDiscount = $product->sale_price && $product->price > $product->sale_price;
    $discountPercent = $hasDiscount
        ? (int) round((1 - ($product->sale_price / $product->price)) * 100)
        : 0;

    $isFree = $product->isFree();
    $instructor = $course->instructor;
    $learnItems = array_values(array_filter($course->what_you_learn ?? []));
    $requirementItems = array_values(array_filter($course->requirements ?? []));
@endphp

@section('page')
<section class="course-detail">
    <div class="course-detail-hero">
        <div class="course-detail-hero-glow" aria-hidden="true"></div>
        <div class="course-detail-hero-dots" aria-hidden="true"></div>

        <div class="course-detail-hero-inner">
            <nav class="course-detail-breadcrumb" aria-label="مسیر صفحه">
                <a href="{{ route('home') }}">خانه</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('courses.index') }}">دوره‌ها</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ Str::limit($product->title, 42) }}</span>
            </nav>

            <div class="course-detail-hero-grid">
                <div class="course-detail-hero-copy">
                    @if ($isFree)
                        <span class="course-detail-badge course-detail-badge--free">رایگان</span>
                    @elseif ($hasDiscount)
                        <span class="course-detail-badge">٪{{ fa_digits($discountPercent) }} تخفیف</span>
                    @else
                        <span class="course-detail-badge">دوره تخصصی</span>
                    @endif

                    <h1>{{ $product->title }}</h1>

                    @if ($product->subtitle)
                        <p class="course-detail-lead">{{ $product->subtitle }}</p>
                    @endif

                    <ul class="course-detail-meta" aria-label="اطلاعات دوره">
                        @if ($instructor)
                            <li>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3L3 8L12 13L21 8L12 3Z" /><path d="M6 10V15C6 17 8.7 19 12 19C15.3 19 18 17 18 15V10" /></svg>
                                <span>مدرس: {{ $instructor->name }}</span>
                            </li>
                        @endif
                        @if ($levelLabel)
                            <li>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19H20" /><path d="M7 19V10" /><path d="M12 19V5" /><path d="M17 19V13" /></svg>
                                <span>سطح {{ $levelLabel }}</span>
                            </li>
                        @endif
                        @if ($lessonsCount > 0)
                            <li>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6H20V18H4Z" /><path d="M8 10H16" /><path d="M8 14H13" /></svg>
                                <span>{{ fa_digits($lessonsCount) }} درس</span>
                            </li>
                        @endif
                        @if ($durationLabel)
                            <li>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" /><path d="M12 7V12L15 14" /></svg>
                                <span>{{ $durationLabel }}</span>
                            </li>
                        @endif
                    </ul>
                </div>

                <div class="course-detail-cover{{ $isFree && ! $product->featured_image ? ' course-detail-cover--free' : '' }}" aria-hidden="{{ $product->featured_image ? 'false' : 'true' }}">
                    @if ($product->featured_image)
                        <img src="{{ $product->featured_image }}" alt="{{ $product->title }}" width="640" height="400" loading="eager">
                    @else
                        <span>{{ $product->title }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="course-detail-body">
        @if (session('error'))
            <div class="course-detail-alert" role="alert">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="course-detail-alert course-detail-alert--ok" role="status">{{ session('success') }}</div>
        @endif

        <div class="course-detail-layout">
            <div class="course-detail-main">
                @if (count($learnItems))
                    <section class="course-detail-section" aria-labelledby="course-learn-heading">
                        <h2 id="course-learn-heading">چه چیزهایی یاد می‌گیرید</h2>
                        <ul class="course-detail-learn">
                            @foreach ($learnItems as $item)
                                <li>
                                    <span class="course-detail-learn-mark" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="M5 12.5L10 17.5L19 7.5" /></svg>
                                    </span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if (filled($product->description))
                    <section class="course-detail-section" aria-labelledby="course-about-heading">
                        <h2 id="course-about-heading">درباره دوره</h2>
                        <div class="course-detail-prose">{!! nl2br(e($product->description)) !!}</div>
                    </section>
                @endif

                @if (count($requirementItems))
                    <section class="course-detail-section" aria-labelledby="course-req-heading">
                        <h2 id="course-req-heading">پیش‌نیازها</h2>
                        <ul class="course-detail-reqs">
                            @foreach ($requirementItems as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($sectionsCount > 0)
                    <section class="course-detail-section" aria-labelledby="course-outline-heading">
                        <div class="course-detail-outline-head">
                            <h2 id="course-outline-heading">سرفصل دوره</h2>
                            <p>{{ fa_digits($sectionsCount) }} فصل · {{ fa_digits($lessonsCount) }} درس</p>
                        </div>

                        <div class="course-detail-outline">
                            @foreach ($course->sections as $sectionIndex => $section)
                                <details class="course-detail-chapter" @if ($sectionIndex === 0) open @endif>
                                    <summary>
                                        <span class="course-detail-chapter-index" aria-hidden="true">{{ fa_digits($sectionIndex + 1) }}</span>
                                        <span class="course-detail-chapter-title">{{ $section->title }}</span>
                                        <span class="course-detail-chapter-count">{{ fa_digits($section->lessons->count()) }} درس</span>
                                        <span class="course-detail-chapter-chevron" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><path d="M6 9L12 15L18 9" /></svg>
                                        </span>
                                    </summary>
                                    <ul class="course-detail-lessons">
                                        @foreach ($section->lessons as $lesson)
                                            <li>
                                                <span class="course-detail-lesson-icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8" /><path d="M10 8.5L16 12L10 15.5Z" /></svg>
                                                </span>
                                                <span class="course-detail-lesson-title">{{ $lesson->title }}</span>
                                                @if ($lesson->is_free_preview)
                                                    <a href="{{ route('courses.preview', [$product->slug, $lesson->slug]) }}" class="course-detail-preview">
                                                        پیش‌نمایش
                                                    </a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="course-detail-aside" aria-label="{{ $isEnrolled ? 'دسترسی دوره' : 'ثبت‌نام در دوره' }}">
                <div class="course-detail-buy">
                    @if ($isEnrolled)
                        @include('theme::partials.course-access', [
                            'product' => $product,
                            'course' => $course,
                            'license' => $license ?? null,
                            'spotUrl' => $spotUrl ?? null,
                        ])
                    @else
                        <div class="course-detail-buy-price">
                            @if ($isFree)
                                <span class="course-detail-price-current is-free">رایگان</span>
                            @elseif ($hasDiscount)
                                <del class="course-detail-price-old">{{ fa_digits(number_format($product->price)) }} تومان</del>
                                <div class="course-detail-price-row">
                                    <span class="course-detail-price-current">{{ fa_digits(number_format($product->sale_price)) }} تومان</span>
                                    <span class="course-detail-price-off">٪{{ fa_digits($discountPercent) }}</span>
                                </div>
                            @else
                                <span class="course-detail-price-current">{{ fa_digits(number_format($product->effectivePrice())) }} تومان</span>
                            @endif
                        </div>

                        @if ($isFree)
                            <a href="{{ route('courses.enroll-free', $product->slug) }}" class="course-detail-cta">ثبت‌نام رایگان</a>
                        @else
                            <form method="POST" action="{{ route('cart.add', $product) }}">
                                @csrf
                                <button type="submit" class="course-detail-cta">افزودن به سبد خرید</button>
                            </form>
                        @endif
                    @endif

                    <ul class="course-detail-buy-facts">
                        @if ($lessonsCount > 0)
                            <li>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6H20V18H4Z" /><path d="M8 10H16" /><path d="M8 14H13" /></svg>
                                <span>{{ fa_digits($lessonsCount) }} درس آموزشی</span>
                            </li>
                        @endif
                        @if ($durationLabel)
                            <li>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" /><path d="M12 7V12L15 14" /></svg>
                                <span>مدت: {{ $durationLabel }}</span>
                            </li>
                        @endif
                        @if ($levelLabel)
                            <li>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19H20" /><path d="M7 19V10" /><path d="M12 19V5" /><path d="M17 19V13" /></svg>
                                <span>سطح {{ $levelLabel }}</span>
                            </li>
                        @endif
                        @if ($instructor)
                            <li>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3L3 8L12 13L21 8L12 3Z" /><path d="M6 10V15C6 17 8.7 19 12 19C15.3 19 18 17 18 15V10" /></svg>
                                <span>مدرس: {{ $instructor->name }}</span>
                            </li>
                        @endif
                        <li>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3L4 7V12C4 16.5 7.4 20.4 12 21C16.6 20.4 20 16.5 20 12V7L12 3Z" /><path d="M9.5 12L11.2 13.7L14.8 10" /></svg>
                            <span>{{ $isEnrolled ? 'پخش از طریق اسپات‌پلیر' : 'دسترسی دائمی پس از ثبت‌نام' }}</span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>

    <div class="course-detail-mobile-cta" aria-label="{{ $isEnrolled ? 'دسترسی سریع' : 'ثبت‌نام سریع' }}">
        @if ($isEnrolled)
            @php
                $mobileSpotUrl = $spotUrl ?? null;
                $mobileIssued = ($license->status ?? null) === 'issued' && filled($mobileSpotUrl);
                $mobileFailed = ($license->status ?? null) === 'failed';
                $mobileHasSpot = filled($course->spotplayer_course_id ?? null);
            @endphp
            <div class="course-detail-mobile-cta-price">
                @if ($mobileIssued)
                    <strong>آماده تماشا</strong>
                @elseif ($mobileFailed)
                    <strong>نیاز به پیگیری</strong>
                @elseif ($mobileHasSpot)
                    <strong>در حال آماده‌سازی</strong>
                @else
                    <strong>ثبت‌نام شدید</strong>
                @endif
            </div>
            @if ($mobileIssued)
                <a href="{{ $mobileSpotUrl }}" class="course-detail-cta course-detail-cta--compact" target="_blank" rel="noopener noreferrer">اسپات‌پلیر</a>
            @elseif ($mobileFailed || $mobileHasSpot)
                <form method="POST" action="{{ route('courses.license.refresh', $product->slug) }}">
                    @csrf
                    <button type="submit" class="course-detail-cta course-detail-cta--compact">بررسی دوباره</button>
                </form>
            @else
                <a href="{{ route('panel.courses') }}" class="course-detail-cta course-detail-cta--compact">دوره‌های من</a>
            @endif
        @else
            <div class="course-detail-mobile-cta-price">
                @if ($isFree)
                    <strong>رایگان</strong>
                @else
                    <strong>{{ fa_digits(number_format($product->effectivePrice())) }} تومان</strong>
                @endif
            </div>
            @if ($isFree)
                <a href="{{ route('courses.enroll-free', $product->slug) }}" class="course-detail-cta course-detail-cta--compact">ثبت‌نام رایگان</a>
            @else
                <form method="POST" action="{{ route('cart.add', $product) }}">
                    @csrf
                    <button type="submit" class="course-detail-cta course-detail-cta--compact">افزودن به سبد</button>
                </form>
            @endif
        @endif
    </div>
</section>
@endsection
