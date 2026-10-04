@php
    $license = $license ?? null;
    $spotUrl = $spotUrl ?? null;
    $hasSpotCourse = filled($course->spotplayer_course_id ?? null);
    $licenseStatus = $license?->status;
    $licenseKey = $license?->license_key;
    if (blank($spotUrl) && filled($licenseKey)) {
        $spotUrl = rtrim(config('cms.spotplayer.player_url', 'https://app.spotplayer.ir'), '/').'/?license='.$licenseKey;
    }
    $isIssued = $licenseStatus === 'issued' && filled($spotUrl);
    $isPending = $hasSpotCourse && (
        ! $license
        || $licenseStatus === 'pending'
        || ($licenseStatus === 'issued' && blank($spotUrl) && blank($licenseKey))
    );
    $isFailed = $hasSpotCourse && $licenseStatus === 'failed';
@endphp

<div class="course-detail-access" data-course-access>
    <p class="course-detail-access-label">دسترسی شما</p>

    @if ($isIssued)
        <a
            href="{{ $spotUrl }}"
            class="course-detail-cta"
            target="_blank"
            rel="noopener noreferrer"
        >مشاهده در اسپات‌پلیر</a>

        @if ($licenseKey)
            <div class="course-detail-license">
                <label for="course-license-key">کد لایسنس</label>
                <div class="course-detail-license-row">
                    <input
                        id="course-license-key"
                        type="text"
                        value="{{ $licenseKey }}"
                        readonly
                        dir="ltr"
                        autocomplete="off"
                    >
                    <button
                        type="button"
                        class="course-detail-license-copy"
                        data-copy-license
                        data-copy-value="{{ $licenseKey }}"
                    >کپی</button>
                </div>
            </div>
        @endif

        <ol class="course-detail-access-steps">
            <li>اپ اسپات‌پلیر را نصب کنید.</li>
            <li>با دکمه بالا دوره را باز کنید یا لایسنس را وارد کنید.</li>
            <li>ویدیوها را داخل اپ تماشا کنید.</li>
        </ol>
    @elseif ($isFailed)
        <p class="course-detail-access-state course-detail-access-state--error" role="alert">
            صدور لایسنس ناموفق بود.
        </p>
        <form method="POST" action="{{ route('courses.license.refresh', $product->slug) }}">
            @csrf
            <button type="submit" class="course-detail-cta">تلاش دوباره</button>
        </form>
        <a href="{{ route('contact') }}" class="course-detail-access-secondary">تماس با پشتیبانی</a>
    @elseif ($isPending)
        <p class="course-detail-access-state" role="status">
            دسترسی اسپات‌پلیر در حال آماده‌سازی است.
        </p>
        <form method="POST" action="{{ route('courses.license.refresh', $product->slug) }}">
            @csrf
            <button type="submit" class="course-detail-cta course-detail-cta--quiet">بررسی دوباره</button>
        </form>
        <ol class="course-detail-access-steps">
            <li>معمولاً چند دقیقه طول می‌کشد.</li>
            <li>بعد از آماده‌شدن، دکمه مشاهده فعال می‌شود.</li>
        </ol>
    @else
        <p class="course-detail-access-state" role="status">
            شما در این دوره ثبت‌نام کرده‌اید.
        </p>
        <a href="{{ route('panel.courses') }}" class="course-detail-cta course-detail-cta--quiet">دوره‌های من</a>
    @endif
</div>
