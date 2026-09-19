<section class="faq-carousel-section" id="popularFaqs">
    <div class="faq-carousel-container">
        <div class="faq-section-header">
            <h2>پرتکرارترین سوالات حسابداری</h2>
        </div>

        <div class="faq-slider">
            <button class="faq-slider-btn faq-prev" type="button" aria-label="سوال قبلی">
                <svg viewBox="0 0 24 24"><path d="M15 5L8 12L15 19" /></svg>
            </button>

            <div class="faq-viewport">
                <div class="faq-track">
                    @foreach ($faqs as $faq)
                        <a href="{{ $faq['href'] ?? route('blog.index') }}" class="faq-card">
                            <div class="faq-icon">
                                <svg viewBox="0 0 80 80">
                                    <circle cx="40" cy="40" r="27" />
                                    <path d="M30 32 C30 25 35 21 41 21 C48 21 53 25 53 31 C53 37 49 40 44 43 C41 45 39 48 39 52" />
                                    <circle cx="39" cy="59" r="1.8" class="faq-dot" />
                                    <path d="M27 18C30 13 35 11 40 11" />
                                    <path d="M53 18C50 13 45 11 40 11" />
                                    <circle cx="27" cy="20" r="3" />
                                    <circle cx="53" cy="20" r="3" />
                                </svg>
                            </div>
                            <p>{{ $faq['q'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>

            <button class="faq-slider-btn faq-next" type="button" aria-label="سوال بعدی">
                <svg viewBox="0 0 24 24"><path d="M9 5L16 12L9 19" /></svg>
            </button>
        </div>
    </div>
</section>
