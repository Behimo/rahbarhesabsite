@extends('theme::layouts.site')

@section('page')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h1 class="mb-4 text-3xl font-extrabold text-teal-900">{{ $product->title }}</h1>
            @if ($product->subtitle)
                <p class="mb-4 text-lg text-slate-600">{{ $product->subtitle }}</p>
            @endif
            <div class="prose mb-8 max-w-none text-slate-700">{!! nl2br(e($product->description)) !!}</div>

            @if ($course->what_you_learn)
                <h2 class="mb-3 text-xl font-bold text-teal-800">سرفصل و محتوا</h2>
                <ul class="mb-8 list-disc space-y-1 ps-5 text-slate-600">
                    @foreach ($course->what_you_learn as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif

            <h2 class="mb-4 text-xl font-bold text-teal-800">فصل‌های دوره</h2>
            @foreach ($course->sections as $section)
                <div class="rh-card mb-4 p-4">
                    <h3 class="mb-2 font-semibold">{{ $section->title }}</h3>
                    <ul class="space-y-1 text-sm text-slate-600">
                        @foreach ($section->lessons as $lesson)
                            <li>{{ $lesson->title }} @if($lesson->is_free_preview)<span class="text-green-600">(پیش‌نمایش)</span>@endif</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div>
            <div class="rh-card sticky top-24 p-6">
                @if ($product->isFree())
                    <p class="mb-4 text-2xl font-bold text-green-600">رایگان</p>
                @else
                    <p class="mb-4 text-2xl font-bold rh-price">{{ number_format($product->effectivePrice()) }} تومان</p>
                @endif

                @if ($isEnrolled)
                    <a href="{{ route('courses.learn', $product->slug) }}" class="rh-btn-primary block text-center">ادامه یادگیری</a>
                @elseif ($product->isFree())
                    <a href="{{ route('courses.enroll-free', $product->slug) }}" class="rh-btn-primary block text-center">ثبت‌نام رایگان</a>
                @else
                    <form method="POST" action="{{ route('cart.add', $product) }}">
                        @csrf
                        <button type="submit" class="rh-btn-primary w-full">افزودن به سبد خرید</button>
                    </form>
                @endif

                <ul class="mt-6 space-y-2 text-sm text-slate-500">
                    <li>{{ $course->sections->sum(fn($s) => $s->lessons->count()) }} درس</li>
                    @if ($course->duration_minutes)<li>{{ $course->duration_minutes }} دقیقه</li>@endif
                    @if ($course->instructor)<li>مدرس: {{ $course->instructor->name }}</li>@endif
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
