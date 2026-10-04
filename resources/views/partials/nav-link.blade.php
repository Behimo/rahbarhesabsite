@php
    $nested = $nested ?? false;

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

    $branchHasCurrent = function (array $nodes) use (&$branchHasCurrent, $resolveHref): bool {
        foreach ($nodes as $node) {
            if (url()->current() === $resolveHref($node)) {
                return true;
            }

            if (! empty($node['children']) && $branchHasCurrent($node['children'])) {
                return true;
            }
        }

        return false;
    };

    $href = $resolveHref($link);
    $children = $link['children'] ?? [];
    $isActive = url()->current() === $href
        || (! empty($link['route']) && request()->routeIs($link['route'], $link['route'].'.*'))
        || $branchHasCurrent($children);
    $target = $link['target'] ?? '_self';
    $label = $link['label'] ?? '';
@endphp

@if (! empty($children))
    <div class="nav-dropdown{{ $nested ? ' nav-dropdown-sub' : '' }}{{ $isActive ? ' active' : '' }}">
        <button class="{{ $nested ? 'dropdown-link' : 'nav-item' }} dropdown-trigger{{ $isActive ? ' active' : '' }}" type="button" aria-expanded="false">
            <span>{{ $label }}</span>
            @if ($nested)
                <svg class="submenu-caret" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M12 5L7 10L12 15" />
                </svg>
            @else
                <svg viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M5 7L10 12L15 7" />
                </svg>
            @endif
        </button>
        <div class="dropdown-menu">
            @foreach ($children as $child)
                @include('partials.nav-link', ['link' => $child, 'nested' => true])
            @endforeach
        </div>
    </div>
@elseif ($nested)
    <a class="dropdown-link{{ $isActive ? ' active' : '' }}" href="{{ $href }}" target="{{ $target }}">{{ $label }}</a>
@else
    <a class="nav-item{{ $isActive ? ' active' : '' }}" href="{{ $href }}" target="{{ $target }}">{{ $label }}</a>
@endif
