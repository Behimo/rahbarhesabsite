<section class="rh-block-products mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    @if(!empty($title))<h2 class="mb-6 text-2xl font-bold text-slate-800">{{ $title }}</h2>@endif
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($products as $product)
            <a href="{{ route('courses.show', $product->slug ?? '#') }}" class="rh-card block p-5 hover:shadow-lg transition-shadow">
                <h3 class="font-bold text-slate-800">{{ $product->title }}</h3>
                @if(!empty($product->subtitle))<p class="mt-2 text-sm text-slate-600">{{ $product->subtitle }}</p>@endif
            </a>
        @endforeach
    </div>
</section>
