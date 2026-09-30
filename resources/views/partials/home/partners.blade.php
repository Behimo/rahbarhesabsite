@php
    $defaults = app(\App\Services\HomePageDefaults::class)->section('partners');
    $heading = $heading ?? $defaults['heading'];
    $items = $items ?? $defaults['items'];
@endphp
<section class="rahbar-partners-section">
      <div class="rahbar-partners-container">
        <div class="rahbar-partners-title">
          <h2>{{ $heading }}</h2>
        </div>

        <div class="rahbar-partners-logos">
          @foreach ($items as $item)
          <a href="{{ ($item['url'] ?? '') !== '' ? $item['url'] : '#' }}" class="partner-logo">
            <img src="{{ block_media($item['image'] ?? '') }}" alt="{{ $item['title'] ?? '' }}" />
          </a>
          @endforeach
        </div>
      </div>
    </section>
