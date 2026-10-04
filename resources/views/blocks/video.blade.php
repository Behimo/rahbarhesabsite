<section class="rh-block-video mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="rh-card overflow-hidden p-2">
        @if(!empty($url))
            <div class="aspect-video w-full overflow-hidden rounded-xl bg-slate-100">
                <iframe src="{{ $url }}" class="h-full w-full" allowfullscreen loading="lazy"></iframe>
            </div>
        @endif
    </div>
</section>
