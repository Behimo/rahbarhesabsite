@php
    $heading = trim($__env->yieldContent('heading'));

    if ($heading === '') {
        $heading = trim($__env->yieldContent('title'));
    }

    $lede = trim($__env->yieldContent('lede'));
    $actions = trim($__env->yieldContent('actions'));
    $backOverride = trim($__env->yieldContent('back'));
    $back = \App\Support\Admin\AdminNavigation::back(request(), $backOverride !== '' ? $backOverride : null);
    $backLabel = trim($__env->yieldContent('back-label'));

    if ($back && $backLabel !== '') {
        $back['label'] = $backLabel;
    }
@endphp

@if ($heading !== '' || $back)
    <header class="admin-page-head">
        <div class="admin-page-head__main">
            @if ($back)
                <a href="{{ $back['url'] }}" class="admin-back">
                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                    <span>{{ $back['label'] }}</span>
                </a>
            @endif
            @if ($heading !== '')
                <div class="admin-page-head__text">
                    <h1 class="admin-page-title">{{ $heading }}</h1>
                    @if ($lede !== '')
                        <p class="admin-page-lede">{{ $lede }}</p>
                    @endif
                </div>
            @endif
        </div>
        @if ($actions !== '')
            <div class="admin-page-actions">
                {!! $actions !!}
            </div>
        @endif
    </header>
@endif
