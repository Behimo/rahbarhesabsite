@extends('theme::layouts.site')

@section('page')
<section class="rh-hero py-16 md:py-24">
    <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
        <h1 class="mb-4 text-3xl font-extrabold md:text-5xl">راهبر حساب؛ خالق رهبران حسابداری</h1>
        <p class="mx-auto mb-8 max-w-2xl text-lg text-teal-100">موسسه آموزشی حسابداری و خدمات مالی و مالیاتی — آموزش تخصصی برای بازار کار</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('courses.index') }}" class="rounded-xl bg-amber-500 px-8 py-3 font-bold text-slate-900 hover:bg-amber-400">مشاهده دوره‌ها</a>
            <a href="{{ route('login') }}" class="rounded-xl border-2 border-white px-8 py-3 font-bold hover:bg-white/10">ورود / ثبت‌نام</a>
        </div>
    </div>
</section>

<section class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($features ?? [] as $feature)
                <div class="rh-card p-6 text-center">
                    <div class="mb-3 text-3xl">{{ $feature['icon'] ?? '📚' }}</div>
                    <h3 class="mb-2 font-bold text-teal-800">{{ $feature['title'] }}</h3>
                    <p class="text-sm text-slate-600">{{ $feature['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="bg-white py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="rh-section-title mb-8 text-center">جدیدترین دوره‌های ما</h2>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($courses as $course)
                <a href="{{ route('courses.show', $course->slug) }}" class="rh-card block overflow-hidden">
                    @if ($course->featured_image)
                        <img src="{{ $course->featured_image }}" alt="{{ $course->title }}" class="h-40 w-full object-cover">
                    @else
                        <div class="flex h-40 items-center justify-center bg-teal-50 text-teal-300">
                            <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                    @endif
                    <div class="p-4">
                        <h3 class="mb-2 line-clamp-2 font-bold text-slate-800">{{ $course->title }}</h3>
                        @if ($course->course?->instructor)
                            <p class="mb-2 text-xs text-slate-500">مدرس: {{ $course->course->instructor->name }}</p>
                        @endif
                        <div class="flex items-center justify-between">
                            @if ($course->isFree())
                                <span class="font-bold text-green-600">رایگان</span>
                            @else
                                <span class="rh-price">{{ number_format($course->effectivePrice()) }} تومان</span>
                            @endif
                            @if ($course->sale_price)
                                <span class="rh-badge-discount">تخفیف</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-8 text-center">
            <a href="{{ route('courses.index') }}" class="rh-btn-primary inline-flex">همه دوره‌ها</a>
        </div>
    </div>
</section>

@if (!empty($freeCourses) && $freeCourses->isNotEmpty())
<section class="py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="rh-section-title mb-8 text-center">آموزش‌های رایگان</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($freeCourses as $course)
                <a href="{{ route('courses.show', $course->slug) }}" class="rh-card flex items-center gap-4 p-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700 font-bold">رایگان</div>
                    <div>
                        <h3 class="font-semibold">{{ $course->title }}</h3>
                        <p class="text-sm text-slate-500">{{ $course->course?->duration_minutes }} دقیقه</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if (!empty($latestPosts) && $latestPosts->isNotEmpty())
<section class="bg-white py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="rh-section-title mb-8 text-center">اخبار و بخشنامه‌های جدید</h2>
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($latestPosts as $post)
                <a href="{{ route('blog.show', $post->slug) }}" class="rh-card p-5">
                    <h3 class="mb-2 font-bold hover:text-teal-700">{{ $post->title }}</h3>
                    <p class="line-clamp-2 text-sm text-slate-600">{{ $post->excerpt }}</p>
                </a>
            @endforeach
        </div>
        <div class="mt-8 text-center">
            <a href="{{ route('blog.index') }}" class="text-teal-700 font-semibold hover:underline">مشاهده همه مقالات →</a>
        </div>
    </div>
</section>
@endif

@if (!empty($faqs))
<section class="py-16">
    <div class="mx-auto max-w-3xl px-4 sm:px-6">
        <h2 class="rh-section-title mb-8 text-center">پرتکرارترین سوالات حسابداری</h2>
        <div class="space-y-3">
            @foreach ($faqs as $faq)
                <details class="rh-card p-4">
                    <summary class="cursor-pointer font-semibold">{{ $faq['q'] }}</summary>
                    <p class="mt-2 text-sm text-slate-600">{{ $faq['a'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
