@php
    $cards = collect($items ?? [])
        ->filter(fn ($item) => filled($item['title'] ?? null) || filled($item['text'] ?? null))
        ->values();
    $columnCount = $cards->count();
    $gridClass = $columnCount > 0 && $columnCount < 5
        ? 'benefits-grid benefits-grid--n'.$columnCount
        : 'benefits-grid';
@endphp

@if ($cards->isNotEmpty())
    <section class="rahbar-benefits">
        <div class="benefits-container">
            <div class="{{ $gridClass }}">
                @foreach ($cards as $index => $card)
                    @php
                        $variant = ($card['variant'] ?? 'down') === 'up' ? 'up' : 'down';
                        $panel = ($card['panel'] ?? 'gray') === 'white' ? 'white' : 'gray';
                    @endphp
                    <article class="benefit-card benefit-card--{{ $variant }}">
                        @include('partials.home.benefit-cap', ['uid' => $index + 1])

                        <div class="benefit-panel benefit-panel--{{ $panel }}">
                            @if (filled($card['title'] ?? null))
                                <h3>{{ $card['title'] }}</h3>
                            @endif
                            @if (filled($card['text'] ?? null))
                                <p>{!! nl2br(e($card['text'])) !!}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
