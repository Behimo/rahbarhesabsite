<span class="rh-side-item__thumb">
    @if ($item['image'])
        <img src="{{ $item['image'] }}" alt="" loading="lazy" decoding="async" width="56" height="56">
    @else
        <span aria-hidden="true">{{ mb_substr($item['title'], 0, 1) }}</span>
    @endif
</span>
<span class="rh-side-item__copy">
    <span class="rh-side-item__title">{{ $item['title'] }}</span>
    @if ($item['meta'])
        <span class="rh-side-item__meta">{{ $item['meta'] }}</span>
    @endif
</span>
