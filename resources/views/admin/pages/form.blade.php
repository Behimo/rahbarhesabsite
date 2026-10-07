@extends('layouts.admin')

@section('title', $page->exists ? 'ویرایش صفحه' : 'صفحه جدید')

@section('heading', $page->exists ? 'ویرایش: '.$page->title : 'صفحه جدید')

@section('lede', 'عنوان و متن صفحه را بنویسید. انتشار و سئو در ستون کناری است.')

@section('actions')
    @if ($page->exists && ! $page->is_system && admin_can('pages', 'delete'))
        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('حذف شود؟')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-label-danger">حذف صفحه</button>
        </form>
    @endif
@endsection

@section('vendor-style')
@include('admin.partials.composer-styles')
@endsection

@section('content')
<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
    @csrf
    @if ($page->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">شناسنامه</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="page-title">عنوان صفحه *</label>
                            <input type="text" name="title" id="page-title" value="{{ old('title', $page->title) }}" class="form-control" required>
                        </div>
                        @if (! $page->is_system)
                            <div class="col-md-6">
                                <label class="form-label" for="page-slug">نامک *</label>
                                <input type="text" name="slug" id="page-slug" value="{{ old('slug', $page->slug) }}" class="form-control" dir="ltr" {{ $page->exists ? '' : 'required' }}>
                                <div class="form-text">آدرس صفحه: /p/<span dir="ltr">slug</span></div>
                            </div>
                        @else
                            <div class="col-md-6">
                                <label class="form-label" for="page-slug">نامک</label>
                                <input type="text" id="page-slug" value="{{ $page->slug }}" class="form-control" dir="ltr" disabled>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if (! $page->is_system)
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">متن صفحه</h5></div>
                    <div class="card-body">
                        <label class="form-label" for="page-body">HTML</label>
                        <textarea name="body_html" id="page-body" rows="16" class="form-control font-monospace" dir="ltr">{{ old('body_html', $page->content['body_html'] ?? '') }}</textarea>
                        <div class="form-text">تگ‌های ساده کافی است: h2، p، ul، strong، a</div>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header"><h5 class="mb-0">سئو</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="page-meta-title">عنوان سئو</label>
                        <input type="text" name="meta_title" id="page-meta-title" value="{{ old('meta_title', $page->meta_title) }}" class="form-control" maxlength="200">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="page-meta-description">توضیحات متا</label>
                        <textarea name="meta_description" id="page-meta-description" rows="3" class="form-control" maxlength="500">{{ old('meta_description', $page->meta_description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="page-keywords">کلمات کلیدی</label>
                        <input type="text" name="meta_keywords" id="page-keywords" value="{{ old('meta_keywords', $page->meta_keywords) }}" class="form-control">
                    </div>
                    <div>
                        <label class="form-label" for="page-og">تصویر Open Graph</label>
                        <input type="text" name="og_image" id="page-og" value="{{ old('og_image', $page->og_image) }}" class="form-control" dir="ltr">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 admin-form-side">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">انتشار</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published" @checked(old('is_published', $page->is_published))>
                        <label class="form-check-label" for="is_published">منتشر شده</label>
                    </div>
                    @if (! $page->is_system)
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="show_in_nav" value="1" class="form-check-input" id="show_in_nav" @checked(old('show_in_nav', $page->show_in_nav))>
                            <label class="form-check-label" for="show_in_nav">نمایش در منو</label>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label" for="page-robots">ربات‌های جستجو</label>
                        <select name="robots" id="page-robots" class="form-select">
                            @foreach (['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'] as $robots)
                                <option value="{{ $robots }}" @selected(old('robots', $page->robots) === $robots)>{{ $robots }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($page->exists)
                        <a href="{{ route('admin.pages.builder', $page) }}" class="btn btn-outline-primary w-100 mb-2">چیدن سکشن‌ها</a>
                    @endif
                    <button type="submit" class="btn btn-primary w-100">ذخیره</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
