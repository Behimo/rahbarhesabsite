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
            @php
                $license = $licenses[$enrollment->course_id] ?? null;
            @endphp
            <div class="rounded-xl border border-white/10 bg-white/5 p-5">
                <h3 class="mb-2 font-semibold text-white">{{ $enrollment->course->product->title }}</h3>
                <div class="mb-3 h-2 rounded-full bg-white/10">
                    <div class="h-2 rounded-full bg-orange-500" style="width: {{ $enrollment->progress_percent }}%"></div>
                </div>
                <p class="mb-2 text-sm text-gray-400">{{ $enrollment->progress_percent }}% تکمیل شده</p>
                <p class="mb-2 text-xs text-gray-500">منبع: {{ $enrollment->source }} — وضعیت: {{ $enrollment->status }}</p>
                @if ($license)
                    <p class="mb-2 text-xs text-gray-300">لایسنس: <span dir="ltr">{{ $license->license_key ?: $license->status }}</span></p>
                    @if ($license->spot_url)
                        <a href="{{ $license->spot_url }}" target="_blank" class="mb-3 inline-block text-sm text-teal-300 hover:underline">باز کردن در اسپات‌پلیر</a>
                    @endif
                @endif
                <a href="{{ route('courses.learn', $enrollment->course->product->slug) }}" class="block text-orange-400 hover:underline">ادامه یادگیری →</a>
            </div>
        @endforeach
    </div>
@endif
@endsection
