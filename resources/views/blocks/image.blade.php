<figure class="rh-block-image mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8 text-center">
    <div class="rh-card overflow-hidden p-4">
        @if(!empty($src))
            <img src="{{ $src }}" alt="{{ $alt ?? '' }}" class="mx-auto max-h-[480px] w-full rounded-xl object-cover">
        @endif
        @if(!empty($caption))
            <figcaption class="mt-3 text-sm text-slate-600">{{ $caption }}</figcaption>
        @endif
    </div>
</figure>
