<section class="cms-block cms-video mx-auto max-w-4xl px-4 py-8">
    <div class="aspect-video overflow-hidden rounded-xl bg-black">
        @if($provider === 'aparat' && $url)
            <iframe src="https://www.aparat.com/video/video/embed/videohash/{{ basename($url) }}/vt/frame" class="h-full w-full" allowfullscreen></iframe>
        @elseif($provider === 'youtube' && $url)
            <iframe src="https://www.youtube.com/embed/{{ basename($url) }}" class="h-full w-full" allowfullscreen></iframe>
        @elseif($url)
            <video src="{{ $url }}" controls class="h-full w-full"></video>
        @endif
    </div>
</section>
