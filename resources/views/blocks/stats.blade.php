<section class="rh-block-stats mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach($items as $item)
            <div class="rh-card p-5 text-center">
                <div class="text-2xl font-bold text-teal-700">{{ $item['value'] ?? '' }}</div>
                <div class="mt-1 text-sm text-slate-600">{{ $item['label'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
</section>
