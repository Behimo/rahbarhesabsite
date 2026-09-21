<section class="cms-block cms-feature-split mx-auto max-w-6xl px-4 py-10">
    <div class="grid items-center gap-8 md:grid-cols-2">
        <div>
            @if (!empty($heading))<h2 class="mb-4 text-2xl font-bold">{{ $heading }}</h2>@endif
            @if (!empty($description))<p class="opacity-80">{{ $description }}</p>@endif
            @if (!empty($cta_text))
                <a href="{{ $cta_url ?? '#' }}" class="mt-4 inline-block rounded-lg bg-teal-600 px-5 py-2 text-white">{{ $cta_text }}</a>
            @endif
        </div>
        @if (!empty($image))
            <img src="{{ $image }}" alt="" class="w-full rounded-xl">
        @endif
    </div>
</section>
