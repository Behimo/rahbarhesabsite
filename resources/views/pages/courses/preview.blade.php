@extends('layouts.site')
@section('page')
<div class="mx-auto max-w-4xl px-4 py-10">
    <p class="mb-2 text-sm text-teal-600">پیش‌نمایش رایگان</p>
    <h1 class="mb-4 text-2xl font-bold">{{ $lesson->title }}</h1>
    @if($lesson->video_url)
        <div class="aspect-video overflow-hidden rounded-xl bg-black">
            @if ($lesson->video_provider === 'aparat')
                <iframe src="https://www.aparat.com/video/video/embed/videohash/{{ basename($lesson->video_url) }}/vt/frame" class="h-full w-full" allowfullscreen></iframe>
            @elseif ($lesson->video_provider === 'youtube')
                <iframe src="https://www.youtube.com/embed/{{ basename($lesson->video_url) }}" class="h-full w-full" allowfullscreen></iframe>
            @else
                <video src="{{ $lesson->video_url }}" controls class="h-full w-full"></video>
            @endif
        </div>
    @endif
    <a href="{{ route('courses.show', $product->slug) }}" class="mt-6 inline-block text-teal-700">← بازگشت به دوره</a>
</div>
@endsection
