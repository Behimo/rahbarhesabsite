@php
    $base = isset($paginationBase)
        ? rtrim((string) $paginationBase, '/')
        : rtrim(parse_url($paginator->path(), PHP_URL_PATH) ?: '/blog', '/');

    $queryString = http_build_query(request()->except('page'));

    $pageUrl = function (int $page) use ($base, $queryString): string {
        $permalinks = app(\App\Services\PermalinkService::class);
        if ($page <= 1) {
            $path = $permalinks->normalizePath($base === '' ? '/' : $base);
        } else {
            $path = $permalinks->normalizePath($base.'/page/'.$page);
        }

        return $queryString !== '' ? $path.'?'.$queryString : $path;
    };
@endphp

@if ($paginator->hasPages())
    <nav class="blog-pager" aria-label="صفحه‌بندی مطالب" dir="rtl">
        <p class="blog-pager__summary">
            نمایش
            <strong>{{ fa_digits($paginator->firstItem()) }}</strong>
            تا
            <strong>{{ fa_digits($paginator->lastItem()) }}</strong>
            از
            <strong>{{ fa_digits($paginator->total()) }}</strong>
            مطلب
        </p>

        <ul class="blog-pager__list">
            <li class="blog-pager__item">
                @if ($paginator->onFirstPage())
                    <span class="blog-pager__btn blog-pager__btn--disabled" aria-disabled="true" aria-label="صفحه قبل">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M7.5 4.5L13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                @else
                    <a class="blog-pager__btn" href="{{ $pageUrl($paginator->currentPage() - 1) }}" rel="prev" aria-label="صفحه قبل">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M7.5 4.5L13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="blog-pager__item" aria-hidden="true">
                        <span class="blog-pager__ellipsis">…</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="blog-pager__item">
                            @if ($page == $paginator->currentPage())
                                <span class="blog-pager__btn blog-pager__btn--current" aria-current="page">
                                    {{ fa_digits($page) }}
                                </span>
                            @else
                                <a class="blog-pager__btn" href="{{ $pageUrl((int) $page) }}" aria-label="صفحه {{ fa_digits($page) }}">
                                    {{ fa_digits($page) }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li class="blog-pager__item">
                @if ($paginator->hasMorePages())
                    <a class="blog-pager__btn" href="{{ $pageUrl($paginator->currentPage() + 1) }}" rel="next" aria-label="صفحه بعد">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M12.5 4.5L7 10l5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                @else
                    <span class="blog-pager__btn blog-pager__btn--disabled" aria-disabled="true" aria-label="صفحه بعد">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M12.5 4.5L7 10l5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
