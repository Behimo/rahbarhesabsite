<section class="cms-block cms-stats mx-auto grid max-w-5xl grid-cols-2 gap-6 px-4 py-8 md:grid-cols-4">
    @foreach($items as $item)
        <div class="text-center">
            <div class="text-3xl font-bold text-teal-700">{{ $item['value'] ?? '' }}</div>
            <div class="text-sm opacity-70">{{ $item['label'] ?? '' }}</div>
        </div>
    @endforeach
</section>
