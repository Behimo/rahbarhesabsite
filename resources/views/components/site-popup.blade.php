@if (! empty($sitePopup))
    <div class="rh-popup" id="rh-popup" hidden
         data-id="{{ $sitePopup['id'] }}"
         data-version="{{ $sitePopup['version'] }}"
         data-delay="{{ $sitePopup['delay'] }}"
         data-frequency="{{ $sitePopup['frequency'] }}">
        <div class="rh-popup__backdrop" data-popup-close></div>
        <div class="rh-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="rh-popup-title" aria-describedby="rh-popup-body">
            <button type="button" class="rh-popup__close" data-popup-close aria-label="بستن">×</button>
            @if (! empty($sitePopup['image']))
                <img class="rh-popup__image" src="{{ $sitePopup['image'] }}" alt="">
            @endif
            <div class="rh-popup__content">
                <h2 id="rh-popup-title">{{ $sitePopup['title'] }}</h2>
                <p id="rh-popup-body">{!! $sitePopup['body'] !!}</p>
                @if (! empty($sitePopup['button_url']))
                    <a class="rh-popup__action" href="{{ $sitePopup['button_url'] }}">{{ $sitePopup['button_label'] }}</a>
                @endif
            </div>
        </div>
    </div>
@endif
