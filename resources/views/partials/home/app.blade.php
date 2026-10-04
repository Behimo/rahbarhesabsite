@php
    $defaults = app(\App\Services\HomePageDefaults::class)->section('app');
    $heading = $heading ?? $defaults['heading'];
    $description = $description ?? $defaults['description'];
    $items = $items ?? $defaults['items'];
    $image = $image ?? $defaults['image'];
    $ctaText = $ctaText ?? ($cta_text ?? $defaults['cta_text']);
    $ctaUrl = $ctaUrl ?? ($cta_url ?? ($appDownloadUrl ?? $defaults['cta_url']));
@endphp
<section class="rahbar-app-section" id="rahbarApp">
      <div class="app-bg-shape" aria-hidden="true"></div>

      <div class="rahbar-app-container">
        <div class="rahbar-app-content">
          <div class="app-headings">
            <h2>{{ $heading }}</h2>
            <p>{{ $description }}</p>
          </div>

          <div class="app-features">
            @foreach ($items as $item)
            <div class="app-feature">
              <div class="feature-icon">
                @include('partials.home.app-feature-icon', ['icon' => $loop->index])
              </div>
              <h3>{{ $item['title'] ?? '' }}</h3>
              <p>{{ $item['text'] ?? '' }}</p>
            </div>
            @endforeach
          </div>

          @if (filled($ctaText))
          <a href="{{ $ctaUrl ?: '#' }}" class="app-download-button" id="appDownloadButton">
            {{ $ctaText }}
          </a>
          @endif
        </div>

        <div class="rahbar-app-visual">
          <div class="app-image-glow"></div>
          <img
            src="{{ block_media($image, 'site/images/apkrahbar2163.webp') }}"
            alt="{{ $heading }}"
            class="app-mockup-image"
          />
        </div>
      </div>
    </section>
