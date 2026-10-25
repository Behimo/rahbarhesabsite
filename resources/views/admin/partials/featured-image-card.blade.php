@php
    $inputId = $inputId ?? 'featured-image-input';
    $previewId = $previewId ?? 'featured-preview';
    $title = $title ?? 'تصویر شاخص';
    $name = $name ?? 'featured_image';
    $value = old($name, $value ?? '');
    $showAlt = ! empty($altName);
    $altValue = $showAlt ? old($altName, $altValue ?? '') : '';
    $help = $help ?? null;
@endphp
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ $title }}</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" data-media-target="#{{ $inputId }}" data-media-preview="#{{ $previewId }}">انتخاب رسانه</button>
    </div>
    <div class="card-body">
        <input type="text" name="{{ $name }}" id="{{ $inputId }}" value="{{ $value }}" class="form-control mb-2" dir="ltr" placeholder="URL تصویر">
        @if ($showAlt)
            <input type="text" name="{{ $altName }}" value="{{ $altValue }}" class="form-control mb-3" placeholder="متن جایگزین تصویر">
        @endif
        <img id="{{ $previewId }}" class="featured-preview w-100 {{ $value ? '' : 'd-none' }}" src="{{ $value }}" alt="">
        @if ($help)
            <div class="form-text mt-2">{{ $help }}</div>
        @endif
    </div>
</div>
