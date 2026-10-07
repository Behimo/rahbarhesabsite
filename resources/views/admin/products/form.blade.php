@extends('layouts.admin')

@php
    $accent = old('accent', $product->accent ?: 'purple');
    $accents = [
        'orange' => 'نارنجی',
        'purple' => 'بنفش',
        'blue' => 'آبی',
        'green' => 'سبز',
    ];
    $dashboardPreview = null;
    if ($product->dashboard_image) {
        $dashboardPreview = str_starts_with($product->dashboard_image, 'http') || str_starts_with($product->dashboard_image, '/')
            ? $product->dashboard_image
            : \Illuminate\Support\Facades\Storage::disk('public')->url($product->dashboard_image);
    }
@endphp

@section('title', $product->exists ? 'ویرایش محصول' : 'محصول جدید')

@section('heading', $product->exists ? 'ویرایش: '.$product->title : 'محصول جدید')

@section('lede', 'متن کارت محصول و تصویر داشبورد را اینجا بنویسید. رنگ، ترتیب و انتشار در ستون کناری است.')

@section('vendor-style')
@include('admin.partials.composer-styles')
@endsection

@section('content')
<form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">معرفی</h5></div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="product-title">عنوان *</label>
                            <input id="product-title" type="text" name="title" value="{{ old('title', $product->title) }}" @class(['form-control', 'is-invalid' => $errors->has('title')]) required>
                            <x-admin.field-error name="title" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="product-slug">نامک *</label>
                            <input id="product-slug" type="text" name="slug" value="{{ old('slug', $product->slug) }}" @class(['form-control', 'is-invalid' => $errors->has('slug')]) dir="ltr" required>
                            <div class="form-text">فقط حروف انگلیسی، عدد، خط تیره و زیرخط.</div>
                            <x-admin.field-error name="slug" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="product-subtitle">زیرعنوان</label>
                        <input id="product-subtitle" type="text" name="subtitle" value="{{ old('subtitle', $product->subtitle) }}" @class(['form-control', 'is-invalid' => $errors->has('subtitle')])>
                        <x-admin.field-error name="subtitle" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="product-description">توضیح کوتاه</label>
                        <textarea id="product-description" name="description" rows="2" @class(['form-control', 'is-invalid' => $errors->has('description')])>{{ old('description', $product->description) }}</textarea>
                        <x-admin.field-error name="description" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="product-audience">مخاطب</label>
                        <input id="product-audience" type="text" name="audience" value="{{ old('audience', $product->audience) }}" @class(['form-control', 'is-invalid' => $errors->has('audience')])>
                        <x-admin.field-error name="audience" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="product-features">ویژگی‌ها</label>
                        <textarea id="product-features" name="features" rows="4" @class(['form-control', 'is-invalid' => $errors->has('features')])>{{ old('features', is_array($product->features) ? implode("\n", $product->features) : '') }}</textarea>
                        <div class="form-text">هر خط یک مورد.</div>
                        <x-admin.field-error name="features" />
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="product-body">متن کامل صفحه</label>
                        <textarea id="product-body" name="body" rows="6" @class(['form-control', 'is-invalid' => $errors->has('body')])>{{ old('body', $product->body) }}</textarea>
                        <x-admin.field-error name="body" />
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">تصویر داشبورد</h5></div>
                <div class="card-body">
                    <p class="form-text mt-0">روی کارت محصول در صفحه اصلی دیده می‌شود. نسبت پیشنهادی ۱۶:۹ است.</p>
                    @if ($dashboardPreview)
                        <img src="{{ $dashboardPreview }}" alt="" class="img-fluid rounded border mb-3" style="max-height: 12rem; object-fit: cover; object-position: top;">
                        <div class="form-check mb-3">
                            <input type="checkbox" name="remove_dashboard_image" value="1" class="form-check-input" id="remove_dashboard_image" @checked(old('remove_dashboard_image'))>
                            <label class="form-check-label text-danger" for="remove_dashboard_image">حذف تصویر فعلی</label>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label" for="product-image-file">آپلود تصویر</label>
                        <input id="product-image-file" type="file" name="dashboard_image_file" @class(['form-control', 'is-invalid' => $errors->has('dashboard_image_file')]) accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">JPG، PNG یا WebP، حداکثر ۵ مگابایت.</div>
                        <x-admin.field-error name="dashboard_image_file" />
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="product-image-url">یا آدرس تصویر</label>
                        <input id="product-image-url" type="text" name="dashboard_image" value="{{ old('dashboard_image', $product->dashboard_image) }}" @class(['form-control', 'is-invalid' => $errors->has('dashboard_image')]) dir="ltr" placeholder="/storage/cms/...">
                        <div class="form-text">از <a href="{{ route('admin.media.index') }}">رسانه</a> آپلود کنید و آدرس را اینجا بگذارید.</div>
                        <x-admin.field-error name="dashboard_image" />
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">سئو</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="product-meta-title">عنوان سئو</label>
                        <input id="product-meta-title" type="text" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" @class(['form-control', 'is-invalid' => $errors->has('meta_title')])>
                        <x-admin.field-error name="meta_title" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="product-meta-description">توضیحات متا</label>
                        <textarea id="product-meta-description" name="meta_description" rows="2" @class(['form-control', 'is-invalid' => $errors->has('meta_description')])>{{ old('meta_description', $product->meta_description) }}</textarea>
                        <x-admin.field-error name="meta_description" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="product-keywords">کلمات کلیدی</label>
                        <input id="product-keywords" type="text" name="meta_keywords" value="{{ old('meta_keywords', $product->meta_keywords) }}" @class(['form-control', 'is-invalid' => $errors->has('meta_keywords')])>
                        <x-admin.field-error name="meta_keywords" />
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="product-og">تصویر Open Graph</label>
                        <input id="product-og" type="text" name="og_image" value="{{ old('og_image', $product->og_image) }}" @class(['form-control', 'is-invalid' => $errors->has('og_image')]) dir="ltr">
                        <x-admin.field-error name="og_image" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 admin-form-side">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">انتشار</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published" @checked(old('is_published', $product->is_published ?? true))>
                        <label class="form-check-label" for="is_published">منتشر شده</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="is_featured" @checked(old('is_featured', $product->is_featured))>
                        <label class="form-check-label" for="is_featured">ویژه</label>
                    </div>
                    <div class="mb-3">
                        <div class="form-label">رنگ کارت</div>
                        <div class="sb-pills sb-pills--2">
                            @foreach ($accents as $value => $label)
                                <label class="sb-pill">
                                    <input type="radio" name="accent" value="{{ $value }}" @checked($accent === $value)>
                                    <span><i class="sb-swatch sb-swatch--{{ $value }}" aria-hidden="true"></i> {{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-admin.field-error name="accent" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="product-cta">متن دکمه</label>
                        <input id="product-cta" type="text" name="cta" value="{{ old('cta', $product->cta) }}" @class(['form-control', 'is-invalid' => $errors->has('cta')])>
                        <x-admin.field-error name="cta" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="product-order">ترتیب</label>
                        <input id="product-order" type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" @class(['form-control', 'is-invalid' => $errors->has('sort_order')]) min="0">
                        <x-admin.field-error name="sort_order" />
                    </div>
                    <button type="submit" class="btn btn-primary w-100">ذخیره</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
