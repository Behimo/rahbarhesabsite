<section class="cms-block cms-features mx-auto max-w-6xl px-4 py-8">
    <div class="grid gap-6 md:grid-cols-3">
        @foreach ($items ?? [] as $item)
            <div class="rounded-xl border p-5">
                <h3 class="mb-2 font-semibold">{{ $item['title'] ?? '' }}</h3>
                <p class="text-sm opacity-70">{{ $item['text'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
</section>
