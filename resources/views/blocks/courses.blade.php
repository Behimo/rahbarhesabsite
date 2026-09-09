<section class="cms-block cms-courses mx-auto max-w-6xl px-4 py-8">
    @if($title)<h2 class="mb-6 text-2xl font-bold">{{ $title }}</h2>@endif
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        @foreach($courses as $course)
            <a href="{{ route('courses.show', $course->slug) }}" class="rounded-xl border p-4 hover:shadow">
                <h3 class="font-semibold">{{ $course->title }}</h3>
                <p class="mt-2 text-sm opacity-70">{{ $course->subtitle }}</p>
            </a>
        @endforeach
    </div>
</section>
