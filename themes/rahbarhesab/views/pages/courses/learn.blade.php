@extends('theme::layouts.site')
@section('page')
<div class="mx-auto max-w-7xl px-4 py-8">
    <a href="{{ route('courses.show', $product->slug) }}" class="text-sm text-teal-700">← {{ $product->title }}</a>
    <div class="mt-6 grid gap-8 lg:grid-cols-4">
        <aside class="lg:col-span-1">
            <div class="max-h-[70vh] overflow-y-auto rounded-xl border bg-white p-3 shadow-sm">
                @foreach ($course->sections as $section)
                    <p class="mb-2 px-2 text-xs font-semibold uppercase text-slate-500">{{ $section->title }}</p>
                    @foreach ($section->lessons as $lesson)
                        <a href="{{ route('courses.learn', [$product->slug, $lesson->slug]) }}"
                           class="mb-1 block rounded-lg px-3 py-2 text-sm {{ $currentLesson->id === $lesson->id ? 'bg-teal-50 text-teal-800' : 'text-slate-600 hover:bg-slate-50' }}">
                            {{ $lesson->title }}
                        </a>
                    @endforeach
                @endforeach
            </div>
        </aside>
        <main class="lg:col-span-3">
            <h1 class="mb-4 text-2xl font-bold text-slate-800">{{ $currentLesson->title }}</h1>
            <div class="mb-6 aspect-video overflow-hidden rounded-xl bg-black">
                @if ($currentLesson->video_provider === 'spotplayer' && !empty($spotplayerUrl))
                    <iframe src="{{ $spotplayerUrl }}" class="h-full w-full" allowfullscreen></iframe>
                @elseif ($currentLesson->video_provider === 'aparat' && $currentLesson->video_url)
                    <iframe src="https://www.aparat.com/video/video/embed/videohash/{{ basename($currentLesson->video_url) }}/vt/frame" class="h-full w-full" allowfullscreen></iframe>
                @elseif ($currentLesson->video_provider === 'youtube' && $currentLesson->video_url)
                    <iframe src="https://www.youtube.com/embed/{{ basename($currentLesson->video_url) }}" class="h-full w-full" allowfullscreen></iframe>
                @elseif ($currentLesson->video_url)
                    <video src="{{ $currentLesson->video_url }}" controls class="h-full w-full"></video>
                @endif
            </div>
            @if($currentLesson->download_url && ($canAccess ?? false))
                <a href="{{ route('courses.lesson.download', [$product->slug, $currentLesson->slug]) }}" class="mb-4 inline-block rounded-lg bg-teal-600 px-4 py-2 text-white">دانلود فایل درس</a>
            @endif
            @if ($currentLesson->content)
                <div class="prose mb-6 max-w-none">{!! $currentLesson->content !!}</div>
            @endif
            @auth
                <form method="POST" action="{{ route('courses.lesson.complete', [$product->slug, $currentLesson->slug]) }}">@csrf<button class="rounded-lg bg-teal-600 px-4 py-2 text-white">تکمیل درس</button></form>
            @endauth
        </main>
    </div>
</div>
@endsection
