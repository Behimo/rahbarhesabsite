@extends('layouts.site')

@section('page')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('courses.show', $product->slug) }}" class="text-sm text-gray-400 hover:text-white">← {{ $product->title }}</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-500/20 p-3 text-green-200">{{ session('success') }}</div>
    @endif

    <div class="grid gap-8 lg:grid-cols-4">
        <aside class="lg:col-span-1">
            <div class="max-h-[70vh] overflow-y-auto rounded-xl border border-white/10 bg-white/5 p-3">
                @foreach ($course->sections as $section)
                    <p class="mb-2 px-2 text-xs font-semibold uppercase text-gray-500">{{ $section->title }}</p>
                    @foreach ($section->lessons as $lesson)
                        <a href="{{ route('courses.learn', [$product->slug, $lesson->slug]) }}"
                           class="mb-1 block rounded-lg px-3 py-2 text-sm {{ $currentLesson->id === $lesson->id ? 'bg-orange-500/20 text-orange-300' : 'text-gray-300 hover:bg-white/5' }}">
                            @if ($progress->get($lesson->id)?->completed_at)
                                <span class="text-green-400">✓</span>
                            @endif
                            {{ $lesson->title }}
                        </a>
                    @endforeach
                @endforeach
            </div>
        </aside>

        <main class="lg:col-span-3">
            <h1 class="mb-4 text-2xl font-bold text-white">{{ $currentLesson->title }}</h1>

            @if ($currentLesson->video_url)
                <div class="mb-6 aspect-video overflow-hidden rounded-xl bg-black">
                    @if ($currentLesson->video_provider === 'aparat')
                        <iframe src="https://www.aparat.com/video/video/embed/videohash/{{ basename($currentLesson->video_url) }}/vt/frame" class="h-full w-full" allowfullscreen></iframe>
                    @elseif ($currentLesson->video_provider === 'youtube')
                        <iframe src="https://www.youtube.com/embed/{{ basename($currentLesson->video_url) }}" class="h-full w-full" allowfullscreen></iframe>
                    @else
                        <video src="{{ $currentLesson->video_url }}" controls class="h-full w-full"></video>
                    @endif
                </div>
            @endif

            @if ($currentLesson->content)
                <div class="prose prose-invert mb-6 max-w-none text-gray-300">
                    {!! $currentLesson->content !!}
                </div>
            @endif

            <form method="POST" action="{{ route('courses.lesson.complete', [$product->slug, $currentLesson->slug]) }}">
                @csrf
                <button type="submit" class="btn-demo">تکمیل درس</button>
            </form>
        </main>
    </div>
</div>
@endsection
