@props(['heroPills' => [], 'hero' => []])

@php
    $rotateWords = $hero['rotate_words'] ?? ['فروش بیشتر', 'کار منظم‌تر', 'رشد سریع‌تر'];
    $rotateJson = json_encode($rotateWords, JSON_UNESCAPED_UNICODE);
    $chips = $hero['chips'] ?? ['پاسخ در ۲۴ ساعت', 'پشتیبانی فارسی', 'بدون پیچیدگی فنی'];
@endphp

<section id="hero" data-section="hero" class="landing-section section-hero relative flex min-h-[90vh] flex-col justify-center overflow-hidden pt-28 pb-14 lg:min-h-screen lg:pt-32 lg:pb-16">
    <div class="hero-backdrop" aria-hidden="true">
        <div class="hero-backdrop__beam"></div>
        <div class="hero-backdrop__mesh"></div>
        <div class="hero-backdrop__particles"></div>
    </div>

    <div class="landing-container relative z-10">
        <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_.95fr] lg:gap-14 xl:gap-20">
            <div class="text-center lg:text-right" data-sr="left">
                <div class="hero-eyebrow mb-6 inline-flex" data-sr="up" data-sr-delay="0">
                    <span class="hero-eyebrow__dot" aria-hidden="true"></span>
                    <span>{{ $hero['eyebrow'] ?? '+۱۲۰۰ کسب‌وکار · ۴ محصول آماده · پروژه اختصاصی' }}</span>
                </div>

                <h1 class="hero-title mb-6" data-sr="up" data-sr-delay="80">
                    <span class="hero-title__line">{{ $hero['title_line1'] ?? 'نرم‌افزار درست برای' }}</span>
                    <span class="hero-title__line hero-title__line--accent">
                        <span
                            class="hero-title__rotate"
                            x-data="textRotate({{ $rotateJson }}, 3200)"
                        >
                            <span
                                class="hero-title__shimmer"
                                x-text="current"
                                :class="{ 'hero-title__shimmer--exit': exiting }"
                            ></span>
                        </span>
                    </span>
                    <span class="hero-title__line">{{ $hero['title_line2'] ?? 'کسب‌وکار شما' }}</span>
                </h1>

                <p class="hero-lead mx-auto mb-9 max-w-xl lg:mx-0" data-sr="up" data-sr-delay="160">
                    {{ $hero['lead'] ?? '۴ محصول آماده داریم — یا اگر نیازتان فرق دارد، همان را برایتان می‌سازیم. یک تماس کافی است تا بفهمید کدام راه برای شما بهتر است.' }}
                </p>

                <div class="mb-9 flex flex-col items-center gap-3 sm:flex-row sm:justify-center lg:justify-start" data-sr="up" data-sr-delay="240">
                    <a href="{{ route('contact') }}" class="hero-cta hero-cta--primary w-full sm:w-auto">
                        <span class="hero-cta__glow" aria-hidden="true"></span>
                        <span>{{ $hero['cta_primary'] ?? 'مشاوره رایگان' }}</span>
                        <svg class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                    <a href="#products" class="hero-cta hero-cta--ghost w-full sm:w-auto">
                        {{ $hero['cta_secondary'] ?? 'ببینید چه ساخته‌ایم' }}
                    </a>
                </div>

                <div class="hero-chips flex flex-wrap items-center justify-center gap-2 lg:justify-start" data-sr="up" data-sr-delay="320">
                    @foreach ($chips as $chip)
                        <span class="hero-chip">{{ $chip }}</span>
                    @endforeach
                </div>
            </div>

            <div class="mx-auto w-full max-w-md lg:max-w-none" data-sr="right" data-sr-delay="200">
                <x-hero-scene :items="$heroPills" />
            </div>
        </div>
    </div>

    @if (count($heroPills))
        @php
            $pillarIcons = [
                '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                '<path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
            ];
            $pillarAccents = ['orange', 'purple', 'blue'];
        @endphp

        <div class="landing-container relative z-10 mt-14 lg:mt-20" data-sr="up" data-sr-delay="400">
            <div class="hero-pillars">
                @foreach ($heroPills as $item)
                    @php
                        $pillarHref = !empty($item['slug'])
                            ? route($item['href'] ?? 'products.show', $item['slug'])
                            : route('products.index');
                    @endphp
                    <a href="{{ $pillarHref }}" class="hero-pillar hero-pillar--{{ $pillarAccents[$loop->index % 3] }} group">
                        <span class="hero-pillar__num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="hero-pillar__icon" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                {!! $pillarIcons[$loop->index % 3] !!}
                            </svg>
                        </span>
                        <span class="hero-pillar__body">
                            <span class="hero-pillar__title">{{ $item['title'] }}</span>
                            <span class="hero-pillar__text">{{ $item['text'] }}</span>
                        </span>
                        <span class="hero-pillar__arrow" aria-hidden="true">
                            <svg class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <a href="#products" class="scroll-indicator z-20 hidden lg:flex" aria-label="اسکرول به بخش بعدی" data-sr="up" data-sr-delay="500">
        <span class="scroll-indicator__text">ببینید چه داریم</span>
        <span class="scroll-indicator__mouse">
            <span class="scroll-indicator__wheel"></span>
        </span>
    </a>
</section>
