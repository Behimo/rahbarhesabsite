@extends('layouts.site')

@section('page')
<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-500/20 p-3 text-red-200">{{ session('error') }}</div>
    @endif

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h1 class="mb-4 text-3xl font-bold text-white">{{ $product->title }}</h1>
            @if ($product->subtitle)
                <p class="mb-4 text-lg text-gray-400">{{ $product->subtitle }}</p>
            @endif
            <div class="prose prose-invert mb-8 max-w-none text-gray-300">
                {!! nl2br(e($product->description)) !!}
            </div>

            @if ($course->what_you_learn)
                <h2 class="mb-3 text-xl font-semibold text-white">چه چیزهایی یاد می‌گیرید</h2>
                <ul class="mb-8 list-disc space-y-1 ps-5 text-gray-300">
                    @foreach ($course->what_you_learn as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif

            <h2 class="mb-4 text-xl font-semibold text-white">سرفصل دوره</h2>
            <div class="space-y-4">
                @foreach ($course->sections as $section)
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                        <h3 class="mb-2 font-medium text-white">{{ $section->title }}</h3>
                        <ul class="space-y-1 text-sm text-gray-400">
                            @foreach ($section->lessons as $lesson)
                                <li class="flex items-center gap-2">
                                    @if ($lesson->is_free_preview)
                                        <span class="text-xs text-green-400">پیش‌نمایش</span>
                                    @endif
                                    {{ $lesson->title }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <div class="sticky top-24 rounded-2xl border border-white/10 bg-white/5 p-6">
                @if ($product->isFree())
                    <p class="mb-4 text-2xl font-bold text-green-400">رایگان</p>
                @else
                    <p class="mb-4 text-2xl font-bold text-orange-400">{{ number_format($product->effectivePrice()) }} تومان</p>
                @endif

                @if ($isEnrolled)
                    <a href="{{ route('courses.learn', $product->slug) }}" class="btn-demo w-full justify-center">ادامه یادگیری</a>
                @elseif ($product->isFree())
                    <a href="{{ route('courses.enroll-free', $product->slug) }}" class="btn-demo w-full justify-center">ثبت‌نام رایگان</a>
                @else
                    <form method="POST" action="{{ route('cart.add', $product) }}">
                        @csrf
                        <button type="submit" class="btn-demo w-full justify-center">افزودن به سبد خرید</button>
                    </form>
                @endif

                <ul class="mt-6 space-y-2 text-sm text-gray-400">
                    <li>{{ $course->sections->sum(fn($s) => $s->lessons->count()) }} درس</li>
                    <li>سطح: {{ $course->level }}</li>
                    @if ($course->duration_minutes)
                        <li>{{ $course->duration_minutes }} دقیقه</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
