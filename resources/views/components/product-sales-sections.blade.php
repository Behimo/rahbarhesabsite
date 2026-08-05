@props(['product'])

@php
    $accentColor = match ($product['accent'] ?? 'orange') {
        'purple' => 'text-purple-400',
        'blue' => 'text-blue-400',
        'green' => 'text-emerald-400',
        default => 'text-bisan-orange',
    };
    $accentBg = match ($product['accent'] ?? 'orange') {
        'purple' => 'bg-purple-500/10 border-purple-500/20',
        'blue' => 'bg-blue-500/10 border-blue-500/20',
        'green' => 'bg-emerald-500/10 border-emerald-500/20',
        default => 'bg-bisan-orange/10 border-bisan-orange/20',
    };
    $accentDot = match ($product['accent'] ?? 'orange') {
        'purple' => 'bg-purple-400',
        'blue' => 'bg-blue-400',
        'green' => 'bg-emerald-400',
        default => 'bg-bisan-orange',
    };
@endphp

@if (!empty($product['stats']))
    <section class="landing-section pt-0 pb-12">
        <div class="landing-container">
            <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
                @foreach ($product['stats'] as $stat)
                    <div class="glass-card-3d p-6 text-center">
                        <p class="text-2xl sm:text-3xl font-extrabold {{ $accentColor }} mb-1">{{ $stat['value'] }}</p>
                        <p class="text-sm text-slate-400">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if (!empty($product['capabilities']))
    <section class="landing-section pt-0 pb-16">
        <div class="landing-container">
            <x-section-header
                badge="امکانات"
                title="همه‌چیز برای"
                :highlight="$product['title']"
                subtitle="ماژول‌های آماده — بدون پیچیدگی فنی، با تمرکز روی نتیجه"
            />
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 mt-10">
                @foreach ($product['capabilities'] as $capability)
                    <div class="glass-card-3d p-6">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl border {{ $accentBg }}">
                            <svg class="h-5 w-5 {{ $accentColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-white mb-2">{{ $capability['title'] }}</h3>
                        <p class="text-sm text-slate-400 leading-7">{{ $capability['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if (!empty($product['use_cases']))
    <section class="landing-section pt-0 pb-16">
        <div class="section-glow section-glow--purple" data-parallax="0.02"></div>
        <div class="landing-container relative">
            <x-section-header
                badge="مناسب برای"
                title="چه کسب‌وکارهایی"
                highlight="از آن استفاده می‌کنند؟"
                subtitle="اگر یکی از این سناریوها شبیه شماست، {{ $product['title'] }} برایتان ساخته شده"
            />
            <div class="grid gap-5 sm:grid-cols-2 mt-10">
                @foreach ($product['use_cases'] as $useCase)
                    <div class="glass-card-3d p-6 flex gap-4">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $accentDot }}"></span>
                        <div>
                            <h3 class="font-semibold text-white mb-1">{{ $useCase['title'] }}</h3>
                            <p class="text-sm text-slate-400 leading-7">{{ $useCase['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if (!empty($product['benefits']))
    <section class="landing-section pt-0 pb-16">
        <div class="landing-container">
            <x-section-header
                badge="مزیت واقعی"
                title="قبل و بعد از"
                :highlight="$product['title']"
                subtitle="مشکلاتی که هر روز می‌شنویم — و راه‌حلی که مشتریان ما تجربه کرده‌اند"
            />
            <div class="grid gap-4 mt-10">
                @foreach ($product['benefits'] as $benefit)
                    <div class="glass-card-3d p-5 sm:p-6 grid gap-4 sm:grid-cols-2 sm:items-center">
                        <div class="flex items-start gap-3">
                            <span class="mt-1 text-red-400 shrink-0" aria-hidden="true">✕</span>
                            <p class="text-sm text-slate-400 leading-7">{{ $benefit['pain'] }}</p>
                        </div>
                        <div class="flex items-start gap-3 sm:border-r sm:border-white/10 sm:pr-6">
                            <span class="mt-1 {{ $accentColor }} shrink-0" aria-hidden="true">✓</span>
                            <p class="text-sm text-slate-200 leading-7">{{ $benefit['solution'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if (!empty($product['testimonial']))
    <section class="landing-section pt-0 pb-16">
        <div class="landing-container">
            <div class="glass-card-3d p-8 sm:p-10 max-w-3xl mx-auto text-center">
                <svg class="h-8 w-8 mx-auto mb-4 {{ $accentColor }} opacity-60" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179z"/>
                </svg>
                <blockquote class="text-lg sm:text-xl text-slate-200 leading-relaxed mb-6">
                    «{{ $product['testimonial']['quote'] }}»
                </blockquote>
                <p class="font-semibold text-white">{{ $product['testimonial']['name'] }}</p>
                <p class="text-sm text-slate-400 mt-1">{{ $product['testimonial']['role'] }}</p>
            </div>
        </div>
    </section>
@endif

@if (!empty($product['faqs']))
    <section class="landing-section pt-0 pb-16">
        <div class="landing-container">
            <x-section-header
                badge="سوالات متداول"
                title="پرسش‌های"
                highlight="رایج"
                subtitle="اگر سوال دیگری دارید، تیم ما آماده پاسخگویی است"
            />
            <div class="max-w-3xl mx-auto mt-10 space-y-3" x-data="{ open: null }">
                @foreach ($product['faqs'] as $i => $faq)
                    <div class="glass-card-3d overflow-hidden">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-4 p-5 text-right"
                            @click="open = open === {{ $i }} ? null : {{ $i }}"
                            :aria-expanded="open === {{ $i }}"
                        >
                            <span class="font-medium text-white">{{ $faq['q'] }}</span>
                            <svg
                                class="h-5 w-5 shrink-0 {{ $accentColor }} transition-transform duration-200"
                                :class="open === {{ $i }} ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            x-show="open === {{ $i }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-cloak
                        >
                            <p class="px-5 pb-5 text-sm text-slate-400 leading-7 border-t border-white/5 pt-4">{{ $faq['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="landing-section pt-0 pb-20">
    <div class="landing-container">
        <div class="glass-card-3d p-8 sm:p-12 text-center relative overflow-hidden">
            <div class="section-glow section-glow--orange absolute inset-0 opacity-40" aria-hidden="true"></div>
            <div class="relative">
                <h2 class="text-2xl sm:text-3xl font-bold text-white mb-3">
                    آماده‌اید {{ $product['title'] }} را امتحان کنید؟
                </h2>
                <p class="text-slate-400 mb-8 max-w-xl mx-auto">
                    دمو رایگان و اختصاصی — تیم ما در ۲۴ ساعت با شما تماس می‌گیرد و پاسخ سوالاتتان را می‌دهد.
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('contact', ['product' => $product['title']]) }}" class="btn-primary">
                        {{ $product['cta'] ?? 'درخواست دمو رایگان' }}
                    </a>
                    @if (!empty($product['website_url']))
                        <a
                            href="{{ $product['website_url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn-outline"
                        >
                            مشاهده سایت {{ $product['title'] }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
