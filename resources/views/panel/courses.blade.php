@extends('theme::layouts.panel')

@section('title', 'دوره‌های من')

@section('content')
<h1 class="panel-title">دوره‌های من</h1>

@if ($enrollments->isEmpty())
    <p class="panel-empty">هنوز در دوره‌ای ثبت‌نام نکرده‌اید.</p>
    <a href="{{ route('courses.index') }}" class="profile-submit">مشاهده دوره‌ها</a>
@else
    <div class="panel-course-grid">
        @foreach ($enrollments as $enrollment)
            @php
                $license = $licenses[$enrollment->course_id] ?? null;
                $product = $enrollment->course->product;
                $hasSpotCourse = filled($enrollment->course->spotplayer_course_id);
                $licenseStatus = $license?->status;
                $spotUrl = $license?->spot_url;
                if (! $spotUrl && $license?->license_key) {
                    $spotUrl = rtrim(config('cms.spotplayer.player_url', 'https://app.spotplayer.ir'), '/').'/?license='.$license->license_key;
                }
                $isIssued = $licenseStatus === 'issued' && filled($spotUrl);
                $isFailed = $hasSpotCourse && $licenseStatus === 'failed';
                $isPending = $hasSpotCourse && ! $isIssued && ! $isFailed;
            @endphp
            <div class="panel-card">
                <h3 class="panel-card__title">{{ $product->title }}</h3>
                <div class="panel-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $enrollment->progress_percent }}" aria-label="پیشرفت دوره">
                    <span style="width: {{ $enrollment->progress_percent }}%"></span>
                </div>
                <p class="panel-muted">{{ fa_digits($enrollment->progress_percent) }}٪ تکمیل شده</p>

                @if ($isIssued)
                    @if ($license?->license_key)
                        <p class="panel-muted">لایسنس: <span dir="ltr">{{ $license->license_key }}</span></p>
                    @endif
                    <a href="{{ $spotUrl }}" target="_blank" rel="noopener noreferrer" class="profile-submit" style="margin-top: 12px; display: inline-flex;">مشاهده در اسپات‌پلیر</a>
                    <a href="{{ route('courses.show', $product->slug) }}" class="panel-inline-link">جزئیات و راهنما</a>
                @elseif ($isFailed)
                    <p class="panel-muted">صدور لایسنس ناموفق بود.</p>
                    <form method="POST" action="{{ route('courses.license.refresh', $product->slug) }}" style="margin-top: 10px;">
                        @csrf
                        <button type="submit" class="profile-submit">تلاش دوباره</button>
                    </form>
                    <a href="{{ route('contact') }}" class="panel-inline-link">تماس با پشتیبانی</a>
                @elseif ($isPending)
                    <p class="panel-muted">دسترسی اسپات‌پلیر در حال آماده‌سازی است.</p>
                    <form method="POST" action="{{ route('courses.license.refresh', $product->slug) }}" style="margin-top: 10px;">
                        @csrf
                        <button type="submit" class="profile-submit">بررسی دوباره</button>
                    </form>
                    <a href="{{ route('courses.show', $product->slug) }}" class="panel-inline-link">جزئیات دوره</a>
                @else
                    <a href="{{ route('courses.show', $product->slug) }}" class="panel-inline-link">مشاهده دوره</a>
                @endif
            </div>
        @endforeach
    </div>
@endif
@endsection
