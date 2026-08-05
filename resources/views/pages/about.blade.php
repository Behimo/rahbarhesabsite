@extends('layouts.site')

@section('page')
    <x-page-hero badge="کی هستیم؟" title="۱۰ سال" subtitle="نرم‌افزار می‌سازیم — هم برای خودمان، هم برای شما." />

    <section class="landing-section pb-20">
        <div class="landing-container">
            <x-breadcrumb :items="[
                ['name' => 'خانه', 'url' => route('home')],
                ['name' => 'درباره ما', 'url' => route('about')],
            ]" />

            @if (!empty($aboutMission))
                <div class="grid gap-6 lg:grid-cols-2 mb-14">
                    <div class="glass-card-3d p-8">
                        <h2 class="text-sm font-medium text-bisan-orange mb-2">ماموریت ما</h2>
                        <p class="text-lg leading-relaxed text-slate-300">{{ $aboutMission['mission'] }}</p>
                    </div>
                    <div class="glass-card-3d p-8">
                        <h2 class="text-sm font-medium text-purple-400 mb-2">چشم‌انداز ما</h2>
                        <p class="text-lg leading-relaxed text-slate-300">{{ $aboutMission['vision'] }}</p>
                    </div>
                </div>
            @endif

            <div class="grid items-center gap-14 lg:grid-cols-2">
                <div>
                    <p class="text-lg leading-relaxed text-slate-300">
                        بیسان تیم برنامه‌نویسی است که ۴ محصول خودش را دارد و برای مشتریان هم پروژه اختصاصی می‌سازد.
                        از سال ۲۰۱۴ در بازار ایران فعالیم و بیش از ۱۲۰۰ کسب‌وکار به ما اعتماد کرده‌اند.
                    </p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        @foreach ($aboutPillars as $point)
                            <div class="pillar-card">
                                <div class="pillar-card__icon">
                                    <svg class="h-5 w-5 text-bisan-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $point['icon'] }}"/>
                                    </svg>
                                </div>
                                <p class="text-sm leading-7 text-slate-300">{{ $point['text'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="about-timeline">
                    @foreach ($aboutTimeline as $step)
                        <div class="about-timeline__item">
                            <div class="about-timeline__node">
                                <span class="about-timeline__year">{{ $step['year'] }}</span>
                            </div>
                            <div class="about-timeline__content glass-card-3d">
                                <h3 class="font-semibold text-white">{{ $step['title'] }}</h3>
                                <p class="mt-1 text-sm text-slate-400">{{ $step['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="glass-card-3d p-6 text-center">
                        <p class="text-3xl font-extrabold text-white">{{ $stat['value'] }}</p>
                        <p class="text-sm text-slate-300">{{ $stat['label'] }}</p>
                        @if (!empty($stat['hint']))
                            <p class="mt-1 text-xs text-slate-500">{{ $stat['hint'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            @if (!empty($partners))
                <div class="mt-16 text-center">
                    <h3 class="text-lg font-semibold text-white mb-6">همکاران و مشتریان ما</h3>
                    <div class="flex flex-wrap items-center justify-center gap-6 lg:gap-10">
                        @foreach ($partners as $partner)
                            <span class="text-lg font-semibold text-white/30">{{ $partner }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
