@extends('theme::layouts.panel')

@section('title', 'داشبورد')

@section('content')
<h1 class="panel-title">داشبورد</h1>

<div class="panel-stats">
    <div class="panel-stat">
        <p class="panel-stat__label">دوره‌های من</p>
        <p class="panel-stat__value">{{ fa_digits($enrollments->count()) }}</p>
    </div>
    <div class="panel-stat">
        <p class="panel-stat__label">سفارش‌ها</p>
        <p class="panel-stat__value">{{ fa_digits($orders->count()) }}</p>
    </div>
</div>

<h2 class="panel-subtitle">دوره‌های اخیر</h2>
@if ($enrollments->isEmpty())
    <p class="panel-empty">هنوز در دوره‌ای ثبت‌نام نکرده‌اید.</p>
    <a href="{{ route('courses.index') }}" class="profile-submit">مشاهده دوره‌ها</a>
@else
    <div class="panel-list">
        @foreach ($enrollments as $enrollment)
            <a href="{{ route('courses.show', $enrollment->course->product->slug) }}" class="panel-list__item">
                <div>
                    <p class="panel-list__title">{{ $enrollment->course->product->title }}</p>
                    <p class="panel-muted">پیشرفت: {{ fa_digits($enrollment->progress_percent ?? 0) }}٪</p>
                </div>
                <span class="panel-list__action">دسترسی</span>
            </a>
        @endforeach
    </div>
@endif
@endsection
