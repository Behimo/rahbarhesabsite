<figure class="cms-block cms-image mx-auto max-w-4xl px-4 py-8 text-center">
    @if($src)<img src="{{ $src }}" alt="{{ $alt }}" class="mx-auto rounded-xl">@endif
    @if($caption)<figcaption class="mt-2 text-sm opacity-70">{{ $caption }}</figcaption>@endif
</figure>
