<section class="cms-block cms-faq mx-auto max-w-3xl px-4 py-8">
    @if($title)<h2 class="mb-6 text-2xl font-bold">{{ $title }}</h2>@endif
    <div class="space-y-4">
        @foreach($items as $item)
            <details class="rounded-lg border p-4">
                <summary class="cursor-pointer font-semibold">{{ $item['question'] ?? '' }}</summary>
                <p class="mt-2 opacity-80">{{ $item['answer'] ?? '' }}</p>
            </details>
        @endforeach
    </div>
</section>
