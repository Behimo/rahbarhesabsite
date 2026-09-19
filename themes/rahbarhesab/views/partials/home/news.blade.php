<section class="news-guides-section" id="latestNewsGuides">
    <div class="news-guides-container">
        <div class="news-guides-header">
            <h2>اخبار و بخشنامه های جدید</h2>
        </div>

        <div class="news-guides-grid">
            @forelse ($latestPosts as $post)
                <a href="{{ route('blog.show', $post->slug) }}" class="news-guide-card">
                    <div class="news-guide-icon">
                        <svg viewBox="0 0 80 80" aria-hidden="true">
                            <circle cx="40" cy="40" r="27" />
                            <path d="M30 32 C30 25 35 21 41 21 C48 21 53 25 53 31 C53 37 49 40 44 43 C41 45 39 48 39 52" />
                            <circle cx="39" cy="59" r="1.7" class="fill-dot" />
                            <path d="M27 18C30 13 35 11 40 11" />
                            <path d="M53 18C50 13 45 11 40 11" />
                            <circle cx="27" cy="20" r="3" />
                            <circle cx="53" cy="20" r="3" />
                        </svg>
                    </div>
                    <div class="news-guide-content">
                        <h3>{{ $post->title }}</h3>
                        <p>{{ $post->excerpt ?: ($post->category?->name ?? 'حسابداری') }}</p>
                    </div>
                </a>
            @empty
                @foreach ($fallbackNews ?? [] as $item)
                    <a href="{{ $item['href'] ?? route('blog.index') }}" class="news-guide-card">
                        <div class="news-guide-icon">
                            <svg viewBox="0 0 80 80" aria-hidden="true">
                                <path d="M24 20H55L62 28V61H24Z" />
                                <path d="M55 20V29H62" />
                                <path d="M30 34H54" />
                                <path d="M30 41H54" />
                                <path d="M30 48H48" />
                            </svg>
                        </div>
                        <div class="news-guide-content">
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['subtitle'] ?? '' }}</p>
                        </div>
                    </a>
                @endforeach
            @endforelse
        </div>
    </div>
</section>
