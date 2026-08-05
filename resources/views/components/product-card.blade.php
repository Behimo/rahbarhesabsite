@props([
    'title',
    'subtitle',
    'description',
    'accent' => 'orange',
    'visual' => 'crm',
    'features' => [],
    'href' => '#',
    'audience' => null,
    'cta' => 'اطلاعات بیشتر',
    'featured' => false,
    'dashboardImage' => null,
])

@php
    $accentClass = in_array($accent, ['orange', 'purple', 'blue', 'green'], true) ? $accent : 'orange';
    $cardClass = 'product-card product-card--' . $accentClass;
    if ($featured) {
        $cardClass .= ' product-card--featured product-card--horizontal';
    }
    if ($dashboardImage) {
        $cardClass .= ' product-card--has-image';
    }
@endphp

<article
    x-data="cardTilt"
    @mousemove="onMove($event)"
    @mouseleave="onLeave()"
    @mouseenter="onEnter()"
    {{ $attributes->merge(['class' => $cardClass]) }}
    :style="cardStyle"
>
    <div class="product-card__glow" :style="glowStyle"></div>
    <div class="product-card__shine"></div>

    <a href="{{ $href }}" class="product-card__link">
        <div class="product-card__preview product-card__preview--{{ $visual }}">
            @if ($dashboardImage)
                <img
                    src="{{ $dashboardImage }}"
                    alt="داشبورد {{ $title }}"
                    class="product-card__screenshot"
                    loading="lazy"
                >
                <div class="product-card__screenshot-fade" aria-hidden="true"></div>
            @else
                <div class="product-card__preview-bg" aria-hidden="true"></div>
            @endif

            @if ($featured)
                <span class="product-card__ribbon">پیشنهاد ما</span>
            @endif

            @unless ($dashboardImage)
            @if ($visual === 'crm')
                <div class="product-card__mock product-card__mock--crm" aria-hidden="true">
                    <div class="product-card__mock-bar">
                        <span></span><span></span><span></span>
                        <em>راهبر · قیف فروش</em>
                    </div>
                    <div class="product-card__pipeline">
                        <div class="product-card__pipeline-col">
                            <small>تماس اول</small>
                            <div class="product-card__pipeline-card"></div>
                            <div class="product-card__pipeline-card product-card__pipeline-card--dim"></div>
                        </div>
                        <div class="product-card__pipeline-col">
                            <small>مذاکره</small>
                            <div class="product-card__pipeline-card product-card__pipeline-card--active"></div>
                        </div>
                        <div class="product-card__pipeline-col">
                            <small>قرارداد</small>
                            <div class="product-card__pipeline-card product-card__pipeline-card--done"></div>
                        </div>
                    </div>
                </div>
            @elseif ($visual === 'finance')
                <div class="product-card__mock product-card__mock--finance" aria-hidden="true">
                    <div class="product-card__mock-bar">
                        <span></span><span></span><span></span>
                        <em>ویلئو · داشبورد</em>
                    </div>
                    <div class="product-card__finance-balance">
                        <small>خالص دارایی</small>
                        <strong>۱۲۴,۵۰۰,۰۰۰</strong>
                    </div>
                    <div class="product-card__finance-chart">
                        <span style="height: 45%"></span>
                        <span style="height: 70%"></span>
                        <span style="height: 55%"></span>
                        <span style="height: 85%"></span>
                        <span style="height: 60%"></span>
                    </div>
                </div>
            @elseif ($visual === 'service')
                <div class="product-card__mock product-card__mock--service" aria-hidden="true">
                    <div class="product-card__mock-bar">
                        <span></span><span></span><span></span>
                        <em>نوژارو · نوبت‌دهی</em>
                    </div>
                    <div class="product-card__calendar">
                        @foreach (['ش', 'ی', 'د', 'س', 'چ'] as $day)
                            <div class="product-card__cal-day">
                                <small>{{ $day }}</small>
                                <span class="{{ $loop->index === 2 ? 'is-booked' : '' }}"></span>
                            </div>
                        @endforeach
                    </div>
                    <div class="product-card__appointment">
                        <span class="product-card__appointment-dot"></span>
                        <span>نوبت بعدی · ۱۴:۳۰</span>
                    </div>
                </div>
            @else
                <div class="product-card__mock product-card__mock--wordpress" aria-hidden="true">
                    <div class="product-card__sync">
                        <div class="product-card__sync-node product-card__sync-node--wp">
                            <img src="{{ asset('images/brands/wordpress.svg') }}" alt="" class="h-6 w-6">
                            <span>وردپرس</span>
                        </div>
                        <div class="product-card__sync-arrow">
                            <span></span><span></span><span></span>
                        </div>
                        <div class="product-card__sync-node product-card__sync-node--crm">
                            <span class="product-card__sync-logo">R</span>
                            <span>راهبر CRM</span>
                        </div>
                    </div>
                    <div class="product-card__sync-feed">
                        <div class="product-card__sync-item"><span class="is-new"></span> لید جدید از فرم تماس</div>
                        <div class="product-card__sync-item"><span></span> سفارش ووکامرس #۴۸۲۱</div>
                    </div>
                </div>
            @endif
            @endunless
        </div>

        <div class="product-card__body">
            <div class="product-card__meta">
                <span class="product-card__badge">{{ $subtitle }}</span>
                @if ($audience)
                    <span class="product-card__audience">{{ $audience }}</span>
                @endif
            </div>

            <h3 class="product-card__title">{{ $title }}</h3>
            <p class="product-card__desc">{{ $description }}</p>

            @if (count($features))
                <ul class="product-card__features">
                    @foreach (array_slice($features, 0, 3) as $feature)
                        <li>{{ $feature }}</li>
                    @endforeach
                </ul>
            @endif

            <span class="product-card__cta">
                <span>{{ $cta }}</span>
                <svg class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </span>
        </div>
    </a>
</article>
