<section class="rh-block-cta mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8 text-center">
    <div class="rh-card p-8 sm:p-10">
        @if(!empty($title))<h2 class="mb-3 text-2xl font-bold text-slate-800">{{ $title }}</h2>@endif
        @if(!empty($subtitle))<p class="mb-6 text-slate-600">{{ $subtitle }}</p>@endif
        @if(!empty($text))<a href="{{ $url ?? '#' }}" class="inline-flex items-center justify-center rounded-xl bg-teal-700 px-6 py-3 font-semibold text-white hover:bg-teal-800">{{ $text }}</a>@endif
    </div>
</section>
