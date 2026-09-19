<section class="courses-carousel-section {{ $sectionClass ?? '' }}" id="{{ $sectionId ?? 'latestCourses' }}">
    <div class="courses-carousel-container">
        <div class="courses-section-header">
            <h2>{{ $title ?? 'جدیدترین دوره های ما' }}</h2>
        </div>

        <div class="courses-slider">
            <button class="courses-slider-btn courses-prev" type="button" aria-label="دوره قبلی">
                <svg viewBox="0 0 24 24"><path d="M15 5L8 12L15 19" /></svg>
            </button>

            <div class="courses-viewport">
                <div class="courses-track">
                    @forelse ($courses as $course)
                        @include('theme::partials.course-card', ['course' => $course, 'isFree' => $isFree ?? false])
                    @empty
                        <article class="course-card">
                            <div class="course-content">
                                <p class="course-title">هنوز دوره‌ای منتشر نشده است.</p>
                            </div>
                        </article>
                    @endforelse
                </div>
            </div>

            <button class="courses-slider-btn courses-next" type="button" aria-label="دوره بعدی">
                <svg viewBox="0 0 24 24"><path d="M9 5L16 12L9 19" /></svg>
            </button>
        </div>
    </div>
</section>
