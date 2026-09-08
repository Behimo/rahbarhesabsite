@extends('layouts.panel')

@section('title', 'دوره‌های من')

@section('content')
<h1 class="mb-6 text-2xl font-bold text-white">دوره‌های من</h1>

@if ($enrollments->isEmpty())
    <p class="text-gray-400">هنوز در دوره‌ای ثبت‌نام نکرده‌اید.</p>
    <a href="{{ route('courses.index') }}" class="btn-demo mt-4 inline-flex">مشاهده دوره‌ها</a>
@else
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($enrollments as $enrollment)
            <div class="rounded-xl border border-white/10 bg-white/5 p-5">
                <h3 class="mb-2 font-semibold text-white">{{ $enrollment->course->product->title }}</h3>
                <div class="mb-3 h-2 rounded-full bg-white/10">
                    <div class="h-2 rounded-full bg-orange-500" style="width: {{ $enrollment->progress_percent }}%"></div>
                </div>
                <p class="mb-4 text-sm text-gray-400">{{ $enrollment->progress_percent }}% تکمیل شده</p>
                <a href="{{ route('courses.learn', $enrollment->course->product->slug) }}" class="text-orange-400 hover:underline">ادامه یادگیری →</a>
            </div>
        @endforeach
    </div>
@endif
@endsection
