@php
    $defaults = app(\App\Services\HomePageDefaults::class)->section('finance');
    $heading = $heading ?? $defaults['heading'];
    $description = $description ?? $defaults['description'];
    $items = $items ?? $defaults['items'];
    $image = $image ?? $defaults['image'];
    $ctaText = $ctaText ?? ($cta_text ?? $defaults['cta_text']);
    $ctaUrl = $ctaUrl ?? ($cta_url ?? $defaults['cta_url']);
@endphp
<section class="rahbar-finance-section" id="rahbarFinance">
      <div class="rahbar-finance-container">
        <div class="rahbar-finance-content">
          <div class="finance-content-inner">
            <h2>{{ $heading }}</h2>

            <p class="finance-description">{{ $description }}</p>

            <ul class="finance-features">
              @foreach ($items as $item)
              <li>
                <span class="finance-check">
                  <svg viewBox="0 0 24 24">
                    <path d="M6.5 12.5L10.2 16L17.8 8.5" />
                  </svg>
                </span>
                <span>{{ $item['text'] ?? ($item['title'] ?? '') }}</span>
              </li>
              @endforeach
            </ul>

            @if (filled($ctaText))
            <a href="{{ $ctaUrl ?: '#' }}" class="finance-more-button">
              <span>{{ $ctaText }}</span>
              <span class="finance-button-arrow">
                <svg viewBox="0 0 24 24">
                  <path d="M14 5L7 12L14 19" />
                </svg>
              </span>
            </a>
            @endif
          </div>
        </div>

        <div class="rahbar-finance-visual">
          <div class="finance-image-wrapper">
            <div class="finance-circle finance-circle-1">
              <img
                src="{{ block_media($image, 'site/images/99605e6a-3a2b-44b4-a380-a9980fb0b822.png') }}"
                alt="{{ $heading }}"
                class="finance-team-image"
              />
            </div>
            <span class="finance-circle finance-circle-2"></span>
            <div class="finance-dots" aria-hidden="true"></div>
          </div>
        </div>
      </div>
    </section>
