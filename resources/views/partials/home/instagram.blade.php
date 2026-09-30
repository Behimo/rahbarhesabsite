@php
    $defaults = app(\App\Services\HomePageDefaults::class)->section('instagram');
    $heading = $heading ?? $defaults['heading'];
    $description = $description ?? $defaults['description'];
    $items = $items ?? $defaults['items'];
    $image = $image ?? $defaults['image'];
    $ctaText = $ctaText ?? ($cta_text ?? $defaults['cta_text']);
    $ctaUrl = $ctaUrl ?? ($cta_url ?? ($contact['instagram'] ?? $defaults['cta_url']));
@endphp
<section class="rahbar-instagram-section" id="rahbarInstagram">
      <div class="rahbar-instagram-container">
        <div class="rahbar-instagram-box">
          <div class="instagram-pattern" aria-hidden="true"></div>

          <div class="instagram-content">
            <div class="instagram-header">
              <h2>{{ $heading }}</h2>
              <p>{{ $description }}</p>
            </div>

            <div class="instagram-features">
              @foreach ($items as $item)
              <div class="instagram-feature">
                <span class="instagram-pin">
                  <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="5"></circle>
                    <path d="M12 13V21"></path>
                    <path d="M9.5 8L11 9.5L14.5 6"></path>
                  </svg>
                </span>
                <div class="instagram-feature-text">
                  <h3>{{ $item['title'] ?? '' }}</h3>
                  <p>{{ $item['text'] ?? '' }}</p>
                </div>
              </div>
              @endforeach
            </div>

            @if (filled($ctaText))
            <a href="{{ $ctaUrl ?: '#' }}" class="instagram-button" target="_blank" rel="noopener noreferrer">
              <span>{{ $ctaText }}</span>
              <span class="instagram-button-arrow">
                <svg viewBox="0 0 24 24">
                  <path d="M14 5L7 12L14 19" />
                </svg>
              </span>
            </a>
            @endif
          </div>
        </div>

        <div class="rahbar-instagram-phone">
          <div class="instagram-phone-shadow"></div>
          <img
            src="{{ block_media($image, 'site/images/instagramupdate.webp') }}"
            alt="{{ $heading }}"
            class="instagram-phone-image"
          />
        </div>
      </div>
    </section>
