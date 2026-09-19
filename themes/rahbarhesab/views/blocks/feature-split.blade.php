@php
    $partials = [
        'app' => 'partials.home.app',
        'finance' => 'partials.home.finance',
        'system' => 'partials.home.system',
        'mentor' => 'partials.home.mentor',
        'instagram' => 'partials.home.instagram',
        'experiences' => 'partials.home.experiences',
        'partners' => 'partials.home.partners',
    ];
    $partial = $partials[$layout ?? 'app'] ?? $partials['app'];
@endphp

@include('theme::'.$partial)
