@extends('theme::layouts.site')

@section('page')
<section class="bg-teal-800 py-12 text-white">
    <div class="mx-auto max-w-7xl px-4 text-center sm:px-6">
        <h1 class="text-3xl font-extrabold">دوره‌های آموزشی حسابداری</h1>
        <p class="mt-2 text-teal-100">آموزش عملی و کاربردی ویژه بازار کار</p>
    </div>
</section>

<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($courses as $course)
            <a href="{{ route('courses.show', $course->slug) }}" class="rh-card block overflow-hidden">
                <div class="p-5">
                    <h3 class="mb-2 font-bold text-slate-800">{{ $course->title }}</h3>
                    <p class="mb-3 line-clamp-2 text-sm text-slate-600">{{ $course->subtitle ?? $course->description }}</p>
                    @if ($course->isFree())
                        <span class="font-bold text-green-600">رایگان</span>
                    @else
                        <span class="rh-price">{{ number_format($course->effectivePrice()) }} تومان</span>
                    @endif
                </div>
            </a>
        @empty
            <p class="col-span-full text-center text-slate-500">دوره‌ای منتشر نشده است.</p>
        @endforelse
    </div>
</div>
@endsection
