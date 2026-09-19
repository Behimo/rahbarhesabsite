@extends('theme::layouts.site')

@section('page')
<section class="bg-teal-800 py-12 text-white">
    <div class="mx-auto max-w-5xl px-4 text-center sm:px-6">
        <h1 class="text-3xl font-extrabold">درباره راهبر حساب</h1>
        <p class="mt-2 text-teal-100">موسسه آموزش حسابداری و خدمات مالی و مالیاتی</p>
    </div>
</section>

<div class="mx-auto max-w-5xl space-y-8 px-4 py-12 sm:px-6 lg:px-8">
    @if (!empty($aboutMission))
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rh-card p-6 sm:p-8">
                <h2 class="mb-3 text-xl font-bold text-slate-800">{{ $aboutMission['title'] ?? 'ماموریت ما' }}</h2>
                <p class="text-slate-600 leading-8">{{ $aboutMission['text'] ?? '' }}</p>
            </div>
            @if (!empty($aboutPillars))
                <div class="rh-card p-6 sm:p-8">
                    <h2 class="mb-4 text-xl font-bold text-slate-800">ارکان اصلی</h2>
                    <ul class="space-y-3">
                        @foreach ($aboutPillars as $pillar)
                            <li class="text-slate-600"><strong class="text-slate-800">{{ $pillar['title'] ?? '' }}:</strong> {{ $pillar['text'] ?? '' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @if (!empty($stats))
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($stats as $stat)
                <div class="rh-card p-4 text-center">
                    <div class="text-2xl font-bold text-teal-700">{{ $stat['value'] ?? '' }}</div>
                    <div class="mt-1 text-sm text-slate-600">{{ $stat['label'] ?? '' }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
