@php
    $defaults = app(\App\Services\HomePageDefaults::class)->section('experiences');
    $heading = $heading ?? $defaults['heading'];
    $items = $items ?? $defaults['items'];
    $ctaText = $ctaText ?? ($cta_text ?? $defaults['cta_text']);
    $ctaUrl = $ctaUrl ?? ($cta_url ?? $defaults['cta_url']);
@endphp
<section class="student-experience-section" id="studentExperiences">
      <div class="student-experience-container">
        <div class="student-experience-header">
          <h2>{{ $heading }}</h2>

          @if (filled($ctaText))
          <a href="{{ $ctaUrl ?: '#' }}" class="student-experience-more">
            <span>{{ $ctaText }}</span>
            <svg viewBox="0 0 24 24">
              <path d="M14 5L7 12L14 19" />
            </svg>
          </a>
          @endif
        </div>

        <div class="student-experience-grid">
          @foreach ($items as $item)
          <a href="{{ ($item['url'] ?? '') !== '' ? $item['url'] : '#' }}" class="student-experience-card">
            <img
              src="{{ block_media($item['image'] ?? '', 'site/images/experience-'.($loop->iteration).'.jpg') }}"
              alt="{{ $item['title'] ?? $heading }}"
            />
            <span class="student-play-button">
              <svg viewBox="0 0 64 64">
                <circle cx="32" cy="32" r="29" />
                <path d="M27 21L45 32L27 43Z" />
              </svg>
            </span>
          </a>
          @endforeach
        </div>
      </div>
    </section>
