@php
    $resolveHref = function (array $link): string {
        if (! empty($link['href'])) {
            return $link['href'];
        }

        if (! empty($link['url'])) {
            return $link['url'];
        }

        if (! empty($link['route'])) {
            return isset($link['params'])
                ? route($link['route'], $link['params'])
                : route($link['route']);
        }

        return '#';
    };

    $href = $resolveHref($link);
    $children = $link['children'] ?? [];
    $isActive = url()->current() === $href
        || (! empty($link['route']) && request()->routeIs($link['route'], $link['route'].'.*'));
    $target = $link['target'] ?? '_self';
@endphp

@if (! empty($children))
    <div class="nav-dropdown">
        <button class="nav-item dropdown-trigger{{ $isActive ? ' active' : '' }}" type="button">
            {{ $link['label'] }}
            <svg viewBox="0 0 20 20">
                <path d="M5 7L10 12L15 7" />
            </svg>
        </button>
        <div class="dropdown-menu">
            @foreach ($children as $child)
                <a href="{{ $resolveHref($child) }}" target="{{ $child['target'] ?? '_self' }}">{{ $child['label'] }}</a>
            @endforeach
        </div>
    </div>
@else
    <a class="nav-item{{ $isActive ? ' active' : '' }}" href="{{ $href }}" target="{{ $target }}">{{ $link['label'] }}</a>
@endif
