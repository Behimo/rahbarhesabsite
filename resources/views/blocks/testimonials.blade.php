<section class="cms-block cms-testimonials mx-auto max-w-5xl px-4 py-8">
    @if($title)<h2 class="mb-6 text-2xl font-bold text-center">{{ $title }}</h2>@endif
    <div class="grid gap-6 md:grid-cols-2">
        @foreach($items as $item)
            <blockquote class="rounded-xl border p-6">
                <p class="mb-4">"{{ $item['quote'] ?? '' }}"</p>
                <footer class="text-sm font-semibold">{{ $item['name'] ?? '' }} — {{ $item['role'] ?? '' }}</footer>
            </blockquote>
        @endforeach
    </div>
</section>
