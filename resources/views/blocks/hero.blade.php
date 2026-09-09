<section class="cms-block cms-hero py-16 text-center" @if($backgroundImage) style="background-image:url('{{ $backgroundImage }}');background-size:cover;" @endif>
    <div class="mx-auto max-w-4xl px-4">
        @if($title)<h1 class="mb-4 text-4xl font-bold">{{ $title }}</h1>@endif
        @if($subtitle)<p class="mb-6 text-lg opacity-80">{{ $subtitle }}</p>@endif
        @if($ctaText)<a href="{{ $ctaUrl }}" class="inline-block rounded-lg bg-teal-600 px-6 py-3 text-white">{{ $ctaText }}</a>@endif
    </div>
</section>
