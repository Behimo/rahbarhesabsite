@extends('layouts.admin')

@section('title', $page->exists ? 'ویرایش صفحه' : 'صفحه جدید')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.pages.index') }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت</a>
    <h4 class="mt-2 mb-0">{{ $page->exists ? 'ویرایش: '.$page->title : 'صفحه جدید' }}</h4>
</div>

@if ($page->exists && ! $page->is_system)
    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="mb-3" onsubmit="return confirm('حذف شود؟')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-label-danger">حذف صفحه</button>
    </form>
@endif

<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
    @csrf
    @if ($page->exists) @method('PUT') @endif

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">اطلاعات پایه</h5></div>
        <div class="card-body">
            <div class="row g-3">
                @if (! $page->is_system)
                    <div class="col-md-6">
                        <label class="form-label">Slug (آدرس) *</label>
                        <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" class="form-control" dir="ltr" {{ $page->exists ? '' : 'required' }}>
                        <div class="form-text">آدرس: /p/<span dir="ltr">slug</span></div>
                    </div>
                @else
                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input type="text" value="{{ $page->slug }}" class="form-control" disabled>
                    </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label">عنوان صفحه *</label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" class="form-control" required>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-4 mt-3">
                <div class="form-check">
                    <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published" @checked(old('is_published', $page->is_published))>
                    <label class="form-check-label" for="is_published">منتشر شده</label>
                </div>
                @if (! $page->is_system)
                    <div class="form-check">
                        <input type="checkbox" name="show_in_nav" value="1" class="form-check-input" id="show_in_nav" @checked(old('show_in_nav', $page->show_in_nav))>
                        <label class="form-check-label" for="show_in_nav">نمایش در منو</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="builder_enabled" value="1" class="form-check-input" id="builder_enabled" @checked(old('builder_enabled', $page->builder_enabled))>
                        <label class="form-check-label" for="builder_enabled">استفاده از صفحه‌ساز</label>
                    </div>
                @endif
            </div>
            @if($page->exists)
                <a href="{{ route('admin.pages.builder', $page) }}" class="btn btn-outline-primary mt-3">باز کردن صفحه‌ساز</a>
            @endif
        </div>
    </div>

    @if (! $page->is_system)
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">محتوای صفحه</h5></div>
            <div class="card-body">
                <label class="form-label">متن صفحه (HTML)</label>
                <textarea name="body_html" rows="16" class="form-control font-monospace" dir="ltr">{{ old('body_html', $page->content['body_html'] ?? '') }}</textarea>
                <div class="form-text">می‌توانید از تگ‌های HTML ساده استفاده کنید: h2, p, ul, strong, a</div>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">سئو</h5></div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">عنوان سئو (meta title)</label>
                <input type="text" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" class="form-control" maxlength="200">
            </div>
            <div class="mb-3">
                <label class="form-label">توضیحات متا</label>
                <textarea name="meta_description" rows="3" class="form-control" maxlength="500">{{ old('meta_description', $page->meta_description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">کلمات کلیدی</label>
                <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $page->meta_keywords) }}" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">تصویر Open Graph (URL)</label>
                <input type="text" name="og_image" value="{{ old('og_image', $page->og_image) }}" class="form-control" dir="ltr">
            </div>
            <div>
                <label class="form-label">Robots</label>
                <select name="robots" class="form-select">
                    @foreach (['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'] as $robots)
                        <option value="{{ $robots }}" @selected(old('robots', $page->robots) === $robots)>{{ $robots }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
</form>
@endsection
