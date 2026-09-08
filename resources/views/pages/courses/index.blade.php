@extends('layouts.site')

@section('page')
<x-page-hero title="دوره‌های آموزشی" subtitle="یادگیری آنلاین با بهترین مدرسین" />

<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @if ($courses->isEmpty())
        <p class="text-center text-gray-400">هنوز دوره‌ای منتشر نشده است.</p>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($courses as $course)
                <a href="{{ route('courses.show', $course->slug) }}" class="group rounded-2xl border border-white/10 bg-white/5 p-6 transition hover:border-orange-500/30">
                    @if ($course->featured_image)
                        <img src="{{ $course->featured_image }}" alt="" class="mb-4 h-40 w-full rounded-xl object-cover">
                    @endif
                    <h3 class="mb-2 text-lg font-semibold text-white group-hover:text-orange-300">{{ $course->title }}</h3>
                    <p class="mb-4 line-clamp-2 text-sm text-gray-400">{{ $course->subtitle ?? Str::limit($course->description, 100) }}</p>
                    <div class="flex items-center justify-between">
                        @if ($course->isFree())
                            <span class="text-green-400">رایگان</span>
                        @else
                            <span class="text-orange-400">{{ number_format($course->effectivePrice()) }} تومان</span>
                        @endif
                        <span class="text-sm text-gray-500">{{ $course->course?->level ?? 'beginner' }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
