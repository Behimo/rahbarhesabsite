<section class="rh-block-testimonials mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    @if(!empty($title))<h2 class="mb-6 text-center text-2xl font-bold text-slate-800">{{ $title }}</h2>@endif
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach($items as $item)
            <blockquote class="rh-card p-6">
                <p class="mb-4 text-slate-600 leading-7">"{{ $item['quote'] ?? '' }}"</p>
                <footer class="text-sm font-semibold text-slate-800">{{ $item['name'] ?? '' }}</footer>
                @if(!empty($item['role']))<div class="text-xs text-slate-500">{{ $item['role'] }}</div>@endif
            </blockquote>
        @endforeach
    </div>
</section>
