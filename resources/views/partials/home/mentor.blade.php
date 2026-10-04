@php
    $defaults = app(\App\Services\HomePageDefaults::class)->section('mentor');
    $heading = $heading ?? $defaults['heading'];
    $description = $description ?? $defaults['description'];
    $items = $items ?? $defaults['items'];
    $image = $image ?? $defaults['image'];
    $ctaText = $ctaText ?? ($cta_text ?? $defaults['cta_text']);
    $ctaUrl = $ctaUrl ?? ($cta_url ?? $defaults['cta_url']);
@endphp
<section class="mentor-section" id="rahbarMentor">
      <div class="mentor-container">
        <div class="mentor-content">
          <div class="mentor-content-inner">
            <h2>{{ $heading }}</h2>

            <p class="mentor-description">{{ $description }}</p>

            <ul class="mentor-features">
              @foreach ($items as $item)
              <li>
                <span class="mentor-check">
                  <svg viewBox="0 0 24 24">
                    <path d="M6.5 12.5L10.2 16L17.8 8.5" />
                  </svg>
                </span>
                <span>{{ $item['text'] ?? ($item['title'] ?? '') }}</span>
              </li>
              @endforeach
            </ul>

            @if (filled($ctaText))
            <a href="{{ $ctaUrl ?: '#' }}" class="mentor-button">
              <span>{{ $ctaText }}</span>
              <span class="mentor-button-arrow">
                <svg viewBox="0 0 24 24">
                  <path d="M14 5L7 12L14 19" />
                </svg>
              </span>
            </a>
            @endif
          </div>
        </div>

        <div class="mentor-graphic" aria-hidden="true">
          <img src="{{ asset('site/images/Group-mentorma.svg') }}" alt="" />
        </div>

        <div class="mentor-visual">
          <div class="mentor-photo-wrapper">
            <img
              src="{{ block_media($image, 'site/images/group-all-mentorma.webp') }}"
              alt="{{ $heading }}"
              class="mentor-photo"
            />
          </div>
        </div>
      </div>
    </section>
