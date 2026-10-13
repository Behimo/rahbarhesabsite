@props([
    'name',
    'id' => null,
    'value' => '',
    'label' => null,
    'rows' => 10,
    'toolbar' => 'basic',
    'mediaButton' => false,
    'mediaButtonLabel' => 'درج از رسانه',
    'error' => null,
    'uploadUrl' => null,
    'rtl' => true,
])

@php
    $fieldId = $id ?? $name;
    $errorKey = $error ?? $name;
    $mediaBtnId = $mediaButton ? $fieldId.'-media-btn' : null;
    $resolvedUploadUrl = $uploadUrl;
    if ($resolvedUploadUrl === null && ($mediaButton || $toolbar === 'full')) {
        $resolvedUploadUrl = route('admin.media.store');
    }
@endphp

@once
    @vite(['resources/js/admin-ckeditor.js'])
@endonce

<div {{ $attributes->class(['mb-3']) }}>
    @if ($label || $mediaButton)
        <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
            @if ($label)
                <label class="form-label mb-0" for="{{ $fieldId }}">{{ $label }}</label>
            @else
                <span></span>
            @endif
            @if ($mediaButton)
                <button type="button" class="btn btn-sm btn-outline-primary" id="{{ $mediaBtnId }}">
                    {{ $mediaButtonLabel }}
                </button>
            @endif
        </div>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $fieldId }}"
        rows="{{ $rows }}"
        data-ckeditor
        data-ckeditor-toolbar="{{ $toolbar }}"
        data-ckeditor-rtl="{{ $rtl ? '1' : '0' }}"
        @if ($mediaBtnId) data-ckeditor-media-btn="{{ $mediaBtnId }}" @endif
        @if ($resolvedUploadUrl) data-ckeditor-upload-url="{{ $resolvedUploadUrl }}" @endif
        @class(['form-control', 'is-invalid' => $errors->has($errorKey)])
    >{{ $value }}</textarea>

    <x-admin.field-error :name="$errorKey" />
</div>
