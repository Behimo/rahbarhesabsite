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
            @endphp
            <div class="panel-card">
                <h3 class="panel-card__title">{{ $enrollment->course->product->title }}</h3>
                <div class="panel-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $enrollment->progress_percent }}" aria-label="پیشرفت دوره">
                    <span style="width: {{ $enrollment->progress_percent }}%"></span>
                </div>
                <p class="panel-muted">{{ fa_digits($enrollment->progress_percent) }}٪ تکمیل شده</p>
                @if ($license)
                    <p class="panel-muted">لایسنس: <span dir="ltr">{{ $license->license_key ?: $license->status }}</span></p>
                    @if ($license->spot_url)
                        <a href="{{ $license->spot_url }}" target="_blank" rel="noopener" class="panel-inline-link">باز کردن در اسپات‌پلیر</a>
                    @endif
                @endif
                <a href="{{ route('courses.learn', $enrollment->course->product->slug) }}" class="panel-inline-link">ادامه یادگیری</a>
            </div>
        @endforeach
    </div>
@endif
@endsection
