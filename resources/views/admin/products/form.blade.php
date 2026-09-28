@extends('layouts.admin')

@section('title', $product->exists ? 'ویرایش محصول' : 'محصول جدید')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.products.index') }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت</a>
    <h4 class="mt-2 mb-0">{{ $product->exists ? 'ویرایش: '.$product->title : 'محصول جدید' }}</h4>
</div>

<form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="card" style="max-width: 48rem;" enctype="multipart/form-data">
    @csrf
    @if ($product->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">عنوان *</label>
                <input type="text" name="title" value="{{ old('title', $product->title) }}" @class(['form-control', 'is-invalid' => $errors->has('title')]) required>
                <x-admin.field-error name="title" />
            </div>
            <div class="col-md-6">
                <label class="form-label">نامک *</label>
                <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" @class(['form-control', 'is-invalid' => $errors->has('slug')]) dir="ltr" required>
                <div class="form-text">فقط حروف انگلیسی، عدد، خط تیره و زیرخط. مثال: <span dir="ltr">rahbar-hesab</span></div>
                <x-admin.field-error name="slug" />
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">زیرعنوان</label>
                <input type="text" name="subtitle" value="{{ old('subtitle', $product->subtitle) }}" @class(['form-control', 'is-invalid' => $errors->has('subtitle')])>
                <x-admin.field-error name="subtitle" />
            </div>
            <div class="col-md-6">
                <label class="form-label">رنگ</label>
                <select name="accent" @class(['form-select', 'is-invalid' => $errors->has('accent')])>
                    @foreach (['orange', 'purple', 'blue', 'green'] as $accent)
                        <option value="{{ $accent }}" @selected(old('accent', $product->accent) === $accent)>{{ $accent }}</option>
                    @endforeach
                </select>
                <x-admin.field-error name="accent" />
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">توضیح کوتاه</label>
            <textarea name="description" rows="2" @class(['form-control', 'is-invalid' => $errors->has('description')])>{{ old('description', $product->description) }}</textarea>
            <x-admin.field-error name="description" />
        </div>

        @php
            $dashboardPreview = null;
            if ($product->dashboard_image) {
                $dashboardPreview = str_starts_with($product->dashboard_image, 'http') || str_starts_with($product->dashboard_image, '/')
                    ? $product->dashboard_image
                    : \Illuminate\Support\Facades\Storage::disk('public')->url($product->dashboard_image);
            }
        @endphp
        <div class="mb-4 rounded border p-3">
            <label class="form-label fw-semibold">تصویر داشبورد محصول</label>
            <p class="form-text mb-3">اسکرین‌شات داشبورد روی کارت محصول در صفحه اصلی و لیست محصولات نمایش داده می‌شود. نسبت پیشنهادی ۱۶:۹ یا عریض‌تر.</p>

            @if ($dashboardPreview)
                <div class="mb-3">
                    <img src="{{ $dashboardPreview }}" alt="پیش‌نمایش داشبورد" class="img-fluid rounded border" style="max-height: 12rem; object-fit: cover; object-position: top;">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="remove_dashboard_image" value="1" class="form-check-input" id="remove_dashboard_image" @checked(old('remove_dashboard_image'))>
                    <label class="form-check-label text-danger" for="remove_dashboard_image">حذف تصویر فعلی</label>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label">آپلود تصویر</label>
                <input type="file" name="dashboard_image_file" @class(['form-control', 'is-invalid' => $errors->has('dashboard_image_file')]) accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPG، PNG یا WebP — حداکثر ۵ مگابایت</div>
                <x-admin.field-error name="dashboard_image_file" />
            </div>
            <div class="mb-0">
                <label class="form-label">یا URL تصویر</label>
                <input type="text" name="dashboard_image" value="{{ old('dashboard_image', $product->dashboard_image) }}" @class(['form-control', 'is-invalid' => $errors->has('dashboard_image')]) dir="ltr" placeholder="/storage/cms/...">
                <x-admin.field-error name="dashboard_image" />
                <div class="form-text">از <a href="{{ route('admin.media.index') }}">مدیریت رسانه</a> آپلود کنید و URL را اینجا بگذارید.</div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">مخاطب</label>
            <input type="text" name="audience" value="{{ old('audience', $product->audience) }}" @class(['form-control', 'is-invalid' => $errors->has('audience')])>
            <x-admin.field-error name="audience" />
        </div>
        <div class="mb-3">
            <label class="form-label">ویژگی‌ها (هر خط یک مورد)</label>
            <textarea name="features" rows="4" @class(['form-control', 'is-invalid' => $errors->has('features')])>{{ old('features', is_array($product->features) ? implode("\n", $product->features) : '') }}</textarea>
            <x-admin.field-error name="features" />
        </div>
        <div class="mb-3">
            <label class="form-label">متن کامل صفحه</label>
            <textarea name="body" rows="6" @class(['form-control', 'is-invalid' => $errors->has('body')])>{{ old('body', $product->body) }}</textarea>
            <x-admin.field-error name="body" />
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">دکمه CTA</label>
                <input type="text" name="cta" value="{{ old('cta', $product->cta) }}" @class(['form-control', 'is-invalid' => $errors->has('cta')])>
                <x-admin.field-error name="cta" />
            </div>
            <div class="col-md-6">
                <label class="form-label">ترتیب</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" @class(['form-control', 'is-invalid' => $errors->has('sort_order')]) min="0">
                <x-admin.field-error name="sort_order" />
            </div>
        </div>

        <hr>
        <h5 class="mb-3">سئو</h5>
        <div class="mb-3">
            <label class="form-label">عنوان سئو</label>
            <input type="text" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" @class(['form-control', 'is-invalid' => $errors->has('meta_title')])>
            <x-admin.field-error name="meta_title" />
        </div>
        <div class="mb-3">
            <label class="form-label">توضیحات متا</label>
            <textarea name="meta_description" rows="2" @class(['form-control', 'is-invalid' => $errors->has('meta_description')])>{{ old('meta_description', $product->meta_description) }}</textarea>
            <x-admin.field-error name="meta_description" />
        </div>
        <div class="mb-3">
            <label class="form-label">کلمات کلیدی</label>
            <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $product->meta_keywords) }}" @class(['form-control', 'is-invalid' => $errors->has('meta_keywords')])>
            <x-admin.field-error name="meta_keywords" />
        </div>
        <div class="mb-3">
            <label class="form-label">تصویر OG</label>
            <input type="text" name="og_image" value="{{ old('og_image', $product->og_image) }}" @class(['form-control', 'is-invalid' => $errors->has('og_image')]) dir="ltr">
            <x-admin.field-error name="og_image" />
        </div>

        <div class="d-flex gap-4 mb-4">
            <div class="form-check">
                <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published" @checked(old('is_published', $product->is_published ?? true))>
                <label class="form-check-label" for="is_published">منتشر شده</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="is_featured" @checked(old('is_featured', $product->is_featured))>
                <label class="form-check-label" for="is_featured">ویژه</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">ذخیره</button>
    </div>
</form>
@endsection
