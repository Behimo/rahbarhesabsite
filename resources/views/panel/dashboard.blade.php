@extends('layouts.panel')

@section('title', 'داشبورد')

@section('content')
<h1 class="mb-6 text-2xl font-bold text-white">داشبورد</h1>

<div class="mb-8 grid gap-4 sm:grid-cols-2">
    <div class="rounded-xl border border-white/10 bg-white/5 p-4">
        <p class="text-sm text-gray-400">دوره‌های من</p>
        <p class="text-3xl font-bold text-white">{{ $enrollments->count() }}</p>
    </div>
    <div class="rounded-xl border border-white/10 bg-white/5 p-4">
        <p class="text-sm text-gray-400">سفارش‌ها</p>
        <p class="text-3xl font-bold text-white">{{ $orders->count() }}</p>
    </div>
</div>

<h2 class="mb-4 text-lg font-semibold text-white">دوره‌های اخیر</h2>
@if ($enrollments->isEmpty())
    <p class="text-gray-400">هنوز در دوره‌ای ثبت‌نام نکرده‌اید.</p>
    <a href="{{ route('courses.index') }}" class="btn-demo mt-4 inline-flex">مشاهده دوره‌ها</a>
@else
    <div class="space-y-3">
        @foreach ($enrollments as $enrollment)
            <a href="{{ route('courses.learn', $enrollment->course->product->slug) }}"
               class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 p-4 hover:border-orange-500/30">
                <div>
                    <p class="font-medium text-white">{{ $enrollment->course->product->title }}</p>
                    <p class="text-sm text-gray-400">پیشرفت: {{ $enrollment->progress_percent }}%</p>
                </div>
                <span class="text-orange-400">ادامه →</span>
            </a>
        @endforeach
    </div>
@endif
@endsection
