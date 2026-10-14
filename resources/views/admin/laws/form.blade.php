@extends('layouts.admin')

@section('title', $law->exists ? 'ویرایش ماده قانونی' : 'ماده قانونی جدید')

@section('heading', $law->exists ? 'ویرایش: '.$law->display_title : 'ماده قانونی جدید')

@section('actions')
    @if ($law->exists)
        <a href="{{ route('admin.laws.preview', $law) }}" class="btn btn-label-secondary" target="_blank" rel="noopener">پیش‌نمایش</a>
        @if ($law->isLive())
            <a href="{{ route('laws.show', $law->slug) }}" class="btn btn-label-primary" target="_blank" rel="noopener">مشاهده در سایت</a>
        @endif
    @endif
@endsection

@section('vendor-style')
@include('admin.partials.composer-styles')
<style>
    .ck-editor__editable { min-height: 220px; direction: rtl; text-align: right; font-family: Vazirmatn, Tahoma, sans-serif; line-height: 1.9; }
    .ck.ck-editor { width: 100%; }
    .media-picker-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: .75rem; max-height: 360px; overflow: auto; }
    .media-picker-item { border: 1px solid var(--bs-border-color); border-radius: .5rem; padding: .35rem; cursor: pointer; background: #fff; text-align: center; }
    .media-picker-item.is-selected, .media-picker-item:hover { border-color: var(--bs-primary); box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), .15); }
    .media-picker-item img { width: 100%; height: 80px; object-fit: cover; border-radius: .35rem; }
    .featured-preview { max-width: 280px; border-radius: .75rem; border: 1px solid var(--bs-border-color); }
</style>
@endsection

@section('content')
<form method="POST" action="{{ $law->exists ? route('admin.laws.update', $law) : route('admin.laws.store') }}" id="law-form">
    @csrf
    @if ($law->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">مشخصات ماده</h5></div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">شماره ماده *</label>
                            <input type="text" name="article_number" value="{{ old('article_number', $law->article_number) }}" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">عنوان موضوع *</label>
                            <input type="text" name="title" id="law-title" value="{{ old('title', $law->title) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <div class="input-group">
                                <input type="text" name="slug" id="law-slug" value="{{ old('slug', $law->slug) }}" class="form-control" dir="ltr" placeholder="خودکار از عنوان">
                                <button type="button" class="btn btn-outline-secondary" id="slug-from-title" title="ساخت از عنوان">از عنوان</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">محتوا</h5></div>
                <div class="card-body">
                    <x-admin.ckeditor name="introduction" id="law-introduction" label="مقدمه" :value="old('introduction', $law->introduction)" :rows="8" toolbar="full" :media-button="true" class="mb-4" />
                    <x-admin.ckeditor name="article_text" id="law-article-text" label="متن ماده" :value="old('article_text', $law->article_text)" :rows="10" toolbar="full" :media-button="true" class="mb-4" />
                    <x-admin.ckeditor name="use_cases" id="law-use-cases" label="موارد استفاده" :value="old('use_cases', $law->use_cases)" :rows="8" toolbar="full" :media-button="true" class="mb-4" />
                    <x-admin.ckeditor name="plain_language" id="law-plain-language" label="به زبان ساده" :value="old('plain_language', $law->plain_language)" :rows="8" toolbar="full" :media-button="true" class="mb-4" />
                    <x-admin.ckeditor name="example" id="law-example" label="مثال" :value="old('example', $law->example)" :rows="8" toolbar="full" :media-button="true" class="mb-4" />
                    <x-admin.ckeditor name="conclusion" id="law-conclusion" label="نتیجه‌گیری" :value="old('conclusion', $law->conclusion)" :rows="8" toolbar="full" :media-button="true" class="mb-0" />
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">سئو</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">عنوان سئو</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $law->meta_title) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">توضیحات متا</label>
                        <textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description', $law->meta_description) }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">کلمات کلیدی</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $law->meta_keywords) }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 admin-form-side">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">انتشار</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published" @checked(old('is_published', $law->is_published))>
                        <label class="form-check-label" for="is_published">منتشر شود</label>
                    </div>
                    <div class="mb-3">
                        <div class="form-label">وضعیت</div>
                        @php $lawStatus = old('status', $law->status ?: 'draft'); @endphp
                        <div class="sb-pills" id="law-status">
                            @foreach ([
                                'draft' => 'پیش‌نویس',
                                'published' => 'منتشر شده',
                                'scheduled' => 'زمان‌بندی‌شده',
                            ] as $value => $label)
                                <label class="sb-pill">
                                    <input type="radio" name="status" value="{{ $value }}" @checked($lawStatus === $value)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاریخ انتشار</label>
                        <input type="datetime-local" name="published_at" value="{{ old('published_at', $law->published_at?->format('Y-m-d\TH:i')) }}" class="form-control" dir="ltr">
                    </div>
                    <div class="mb-3">
                        @include('admin.partials.category-ui')
                        <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                            <label class="form-label mb-0">دسته</label>
                            @if (admin_can('taxonomies', 'view'))
                                <a href="{{ route('admin.categories.index', ['type' => 'law']) }}" class="small">مدیریت درخت</a>
                            @endif
                        </div>
                        @php $selectedCategory = (string) old('category_id', $law->category?->id); @endphp
                        <div class="cat-pick">
                            <label class="cat-pick__row">
                                <input class="form-check-input" type="radio" name="category_id" value="" @checked($selectedCategory === '')>
                                <span class="cat-pick__name">بدون دسته</span>
                            </label>
                            @foreach ($categories as $cat)
                                <label class="cat-pick__row" style="padding-inline-start: {{ .85 + ($cat->treeDepth * 1.15) }}rem">
                                    <input class="form-check-input" type="radio" name="category_id" value="{{ $cat->id }}" @checked($selectedCategory === (string) $cat->id)>
                                    <span>
                                        <span class="cat-pick__name">{{ $cat->name }}</span>
                                        <span class="cat-path">{{ $cat->full_slug }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">ذخیره ماده</button>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">تصویر شاخص</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-media-target="#featured-image-input" data-media-preview="#featured-preview">انتخاب رسانه</button>
                </div>
                <div class="card-body">
                    <input type="text" name="featured_image" id="featured-image-input" value="{{ old('featured_image', $law->featured_image) }}" class="form-control mb-2" dir="ltr" placeholder="URL تصویر">
                    <input type="text" name="featured_image_alt" value="{{ old('featured_image_alt', $law->featured_image_alt) }}" class="form-control mb-3" placeholder="متن جایگزین تصویر">
                    <img id="featured-preview" class="featured-preview w-100 {{ old('featured_image', $law->featured_image) ? '' : 'd-none' }}"
                         src="{{ old('featured_image', $law->featured_image) }}" alt="">
                </div>
            </div>
        </div>
    </div>
</form>

@if ($law->exists)
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">افزودن مورد سیر تحول</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.laws.evolutions.store', $law) }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label" for="evo-date">تاریخ</label>
                    <input id="evo-date" type="text" name="event_date" class="form-control" placeholder="1400/01/01" dir="ltr" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="evo-title">عنوان رویداد</label>
                    <input id="evo-title" type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="evo-desc">شرح تحول</label>
                    <input id="evo-desc" type="text" name="description" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label" for="evo-order">ترتیب</label>
                    <input id="evo-order" type="number" name="sort_order" class="form-control" value="{{ (int) $law->evolutions->max('sort_order') + 1 }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">افزودن</button>
                </div>
            </form>
        </div>
    </div>

    @forelse ($law->evolutions as $evolution)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $evolution->event_date }} — {{ $evolution->title }}</h5>
                <form method="POST" action="{{ route('admin.laws.evolutions.destroy', [$law, $evolution]) }}" onsubmit="return confirm('این مورد حذف شود؟')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                </form>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.laws.evolutions.update', [$law, $evolution]) }}" class="row g-3">
                    @csrf @method('PUT')
                    <div class="col-md-2">
                        <label class="form-label">تاریخ</label>
                        <input type="text" name="event_date" value="{{ old('event_date', $evolution->event_date) }}" class="form-control" dir="ltr" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">عنوان رویداد</label>
                        <input type="text" name="title" value="{{ old('title', $evolution->title) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">شرح تحول</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description', $evolution->description) }}</textarea>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">ترتیب</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $evolution->sort_order) }}" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-primary w-100">ذخیره</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="alert alert-secondary">هنوز موردی برای سیر تحول ثبت نشده است.</div>
    @endforelse
@endif

@include('admin.partials.media-picker-modal')
@endsection

@section('page-script')
<script>
document.getElementById('slug-from-title')?.addEventListener('click', function () {
    const title = document.getElementById('law-title')?.value || '';
    const slugInput = document.getElementById('law-slug');
    if (!slugInput || !title) return;
    slugInput.value = title
        .trim()
        .toLowerCase()
        .replace(/[^\u0600-\u06FFa-z0-9\s-]/gi, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
});
</script>
@include('admin.partials.composer-scripts')
@endsection
