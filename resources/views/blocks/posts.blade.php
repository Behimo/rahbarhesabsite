<section class="cms-block cms-posts mx-auto max-w-6xl px-4 py-8">
    @if($title)<h2 class="mb-6 text-2xl font-bold">{{ $title }}</h2>@endif
    <div class="grid gap-6 md:grid-cols-3">
        @foreach($posts as $post)
            <a href="{{ route('blog.show', $post->slug) }}" class="rounded-xl border p-4 hover:shadow">
                <h3 class="font-semibold">{{ $post->title }}</h3>
                <p class="mt-2 text-sm opacity-70">{{ $post->excerpt }}</p>
            </a>
        @endforeach
    </div>
</section>
