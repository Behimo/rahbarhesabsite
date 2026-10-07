@if ($pageSidebars->isNotEmpty())
<aside class="rh-page-sidebar" aria-label="سایدبار">
    @foreach ($pageSidebars as $widget)
        <section class="rh-side-card" aria-labelledby="rh-side-{{ $widget['id'] }}">
            <div class="rh-side-card__head">
                <h2 id="rh-side-{{ $widget['id'] }}">{{ $widget['title'] }}</h2>
                @if ($widget['more_url'])
                    <a href="{{ $widget['more_url'] }}">همه</a>
                @endif
            </div>
            <ul class="rh-side-list">
                @foreach ($widget['items'] as $item)
                    <li>
                        @if ($item['url'])
                            <a href="{{ $item['url'] }}" class="rh-side-item">
                                @include('components.page-sidebar-item', ['item' => $item])
                            </a>
                        @else
                            <div class="rh-side-item">
                                @include('components.page-sidebar-item', ['item' => $item])
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</aside>
@endif
