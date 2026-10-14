@extends('layouts.admin')
@section('title', 'صفحه‌ساز — '.$page->title)
@section('heading', 'صفحه‌ساز: '.$page->title)
@section('actions')
    @if($page->slug && ! $page->is_system)
        <a href="{{ route('pages.show', $page->slug) }}" target="_blank" rel="noopener" class="btn btn-label-secondary">مشاهده صفحه</a>
    @endif
    <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-label-secondary">تنظیمات صفحه</a>
    <a href="{{ route('admin.pages.revisions', $page) }}" class="btn btn-label-secondary">تاریخچه</a>
@endsection
@section('page-style')
    @vite(['resources/css/admin-page-builder.css'])
@endsection
@section('content')
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('admin.pages.builder.save', $page) }}" id="builder-form" novalidate>
    @csrf
    <input type="hidden" name="builder_content" id="builder-content-input" value="">
    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 5rem; z-index: 2;">
                <div class="card-header fw-semibold">افزودن بلوک</div>
                <div class="list-group list-group-flush" id="block-palette">
                    @foreach($blocks as $block)
                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2" data-add-block="{{ $block['type'] }}">
                            <i class="ti ti-layout-grid-add text-success"></i>
                            {{ $block['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">محتوای صفحه</span>
                    <span class="badge bg-label-primary" id="block-count">{{ count($builderContent['blocks'] ?? []) }} بلوک</span>
                </div>
                <div class="card-body" id="builder-canvas"></div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="ti ti-device-floppy me-1"></i> ذخیره صفحه
                </button>
                <button type="button" class="btn btn-outline-primary" id="preview-builder">
                    <i class="ti ti-eye me-1"></i> پیش‌نمایش
                </button>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 5rem; z-index: 2;">
                <div class="card-header fw-semibold">پیش‌نمایش زنده</div>
                <div class="card-body" id="builder-preview">
                    <p class="text-muted small mb-0">روی «پیش‌نمایش» کلیک کنید.</p>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
window.pageBuilderConfig = {
    builderContent: @json($builderContent),
    blockDefs: @json(collect($blocks)->keyBy('type')),
    previewUrl: @json(route('admin.pages.builder.preview', $page)),
    csrfToken: @json(csrf_token()),
};
</script>
@endsection

@section('page-script')
    @vite(['resources/js/admin-page-builder.js'])
@endsection
