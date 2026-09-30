@php
    $defaults = app(\App\Services\HomePageDefaults::class)->section('system');
    $heading = $heading ?? $defaults['heading'];
    $description = $description ?? $defaults['description'];
    $items = $items ?? $defaults['items'];
    $image = $image ?? $defaults['image'];
    $ctaText = $ctaText ?? ($cta_text ?? $defaults['cta_text']);
    $ctaUrl = $ctaUrl ?? ($cta_url ?? $defaults['cta_url']);
@endphp
<section class="rahbar-system-section" id="rahbarSystem">
      <div class="rahbar-system-container">
        <div class="rahbar-system-visual">
          <div class="rahbar-system-image-wrapper">
            <img
              src="{{ block_media($image, 'site/images/vida-mohammadnia.webp') }}"
              alt="{{ $heading }}"
              class="rahbar-system-image"
            />
          </div>
        </div>

        <div class="rahbar-system-content">
          <div class="rahbar-system-content-inner">
            <h2>{{ $heading }}</h2>

            <p class="rahbar-system-description">{{ $description }}</p>

            <ul class="rahbar-system-features">
              @foreach ($items as $item)
              <li>
                <span class="system-check">
                  <svg viewBox="0 0 24 24">
                    <path d="M6.5 12.5L10.2 16L17.8 8.5" />
                  </svg>
                </span>
                <span>{{ $item['text'] ?? ($item['title'] ?? '') }}</span>
              </li>
              @endforeach
            </ul>

            @if (filled($ctaText))
            <a href="{{ $ctaUrl ?: '#' }}" class="rahbar-system-button">
              <span>{{ $ctaText }}</span>
              <span class="system-button-arrow">
                <svg viewBox="0 0 24 24">
                  <path d="M14 5L7 12L14 19" />
                </svg>
              </span>
            </a>
            @endif
          </div>
        </div>
      </div>
    </section>
