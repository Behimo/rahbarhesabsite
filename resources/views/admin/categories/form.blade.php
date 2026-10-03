@extends('layouts.admin')

@section('title', $category->exists ? 'ویرایش دسته' : 'دسته جدید')

@section('page-style')
<style>
    .cat-form-side { position: sticky; top: 1.25rem; }
    .cat-preview {
        padding: .9rem 1rem;
        border: 1px dashed var(--bs-border-color);
        border-radius: .5rem;
        background: rgba(var(--bs-primary-rgb), .04);
    }
    .cat-preview__url {
        direction: ltr;
        unicode-bidi: isolate;
        overflow-wrap: anywhere;
        font-size: .95rem;
        line-height: 1.6;
    }
    .cat-image-preview {
        display: block;
        width: 100%;
        max-height: 180px;
        object-fit: cover;
        border-radius: .5rem;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
    }
    .cat-parent-list { max-height: 22rem; }
    @media (max-width: 1199.98px) {
        .cat-form-side { position: static; }
    }
</style>
@endsection

@section('content')
@include('admin.partials.category-ui')

@php
    $isPost = $type === 'post';
    $typeLabel = $isPost ? 'بلاگ' : 'محصول و دوره';
    $publicBase = $isPost ? route('blog.index', [], false) : route('courses.index', [], false);
    $selectedParent = (string) old('parent_id', $category->parent_id);
    $active = (string) old('is_active', ($category->is_active ?? true) ? '1' : '0') === '1';
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <a href="{{ route('admin.categories.index', ['type' => $type]) }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت به درخت</a>
        <h4 class="mt-2 mb-1">{{ $category->exists ? 'ویرایش: '.$category->name : 'دستهٔ جدید '.$typeLabel }}</h4>
        <p class="text-muted mb-0">
            {{ $isPost
                ? 'این دسته در فیلتر مقالات دیده می‌شود. انتخاب دستهٔ والد، مطالب زیردسته را هم در فیلتر والد نشان می‌دهد.'
                : 'این دسته در فیلتر دوره‌ها و محصولات دیده می‌شود. یک محصول می‌تواند در چند دسته باشد.' }}
        </p>
    </div>
    @if ($category->exists)
        <a href="{{ route('admin.categories.create', ['type' => $type, 'parent_id' => $category->id]) }}" class="btn btn-label-secondary">
            <i class="ti ti-plus me-1"></i>زیردسته
        </a>
    @endif
</div>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="fw-medium mb-1">این موارد را اصلاح کنید</div>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" id="category-form" data-public-base="{{ $publicBase }}" data-exists="{{ $category->exists ? '1' : '0' }}">
    @csrf
    @if ($category->exists) @method('PUT') @endif
    <input type="hidden" name="type" value="{{ $type }}">

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">نام دسته</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="category-name">نام</label>
                            <input
                                type="text"
                                name="name"
                                id="category-name"
                                value="{{ old('name', $category->name) }}"
                                class="form-control @error('name') is-invalid @enderror"
                                maxlength="100"
                                required
                                autocomplete="off"
                                @error('name') aria-invalid="true" aria-describedby="category-name-error" @enderror
                            >
                            @error('name')
                                <div class="invalid-feedback" id="category-name-error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="category-slug">نامک</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    name="slug"
                                    id="category-slug"
                                    value="{{ old('slug', $category->slug) }}"
                                    class="form-control @error('slug') is-invalid @enderror"
                                    dir="ltr"
                                    maxlength="80"
                                    required
                                    data-slug
                                    autocomplete="off"
                                    spellcheck="false"
                                    @error('slug') aria-invalid="true" aria-describedby="category-slug-help category-slug-error" @else aria-describedby="category-slug-help" @enderror
                                >
                                <button type="button" class="btn btn-outline-secondary" id="slug-from-name">از نام</button>
                            </div>
                            <div class="form-text" id="category-slug-help">حروف انگلیسی، عدد و خط تیره. تا وقتی خودتان نامک را عوض نکنید، از نام ساخته می‌شود.</div>
                            @error('slug')
                                <div class="invalid-feedback d-block" id="category-slug-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">جایگاه در درخت</h5>
                    <a href="{{ route('admin.categories.index', ['type' => $type]) }}" class="small">مشاهدهٔ درخت</a>
                </div>
                <div class="card-body">
                    <fieldset class="mb-3">
                        <legend class="form-label">دستهٔ والد</legend>
                        <label class="visually-hidden" for="parent-filter">جستجو در دسته‌های والد</label>
                        <input type="search" id="parent-filter" class="form-control mb-2" placeholder="جستجو با نام یا مسیر" autocomplete="off">
                        <div class="cat-pick cat-parent-list @error('parent_id') border-danger @enderror" id="parent-list">
                            <label class="cat-pick__row" data-search="سطح اول بدون والد">
                                <input class="form-check-input" type="radio" name="parent_id" value="" data-path="" @checked($selectedParent === '')>
                                <span>
                                    <span class="cat-pick__name">سطح اول</span>
                                    <span class="cat-path">بدون والد</span>
                                </span>
                            </label>
                            @foreach ($parents as $parent)
                                <label class="cat-pick__row" data-search="{{ mb_strtolower($parent->name.' '.$parent->full_slug) }}" style="padding-inline-start: {{ .85 + ($parent->treeDepth * 1.15) }}rem">
                                    <input class="form-check-input" type="radio" name="parent_id" value="{{ $parent->id }}" data-path="{{ $parent->full_slug }}" @checked($selectedParent === (string) $parent->id)>
                                    <span>
                                        <span class="cat-pick__name">{{ $parent->name }}</span>
                                        <span class="cat-path">/{{ $parent->full_slug }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="form-text d-none" id="parent-empty">دسته‌ای با این عبارت پیدا نشد.</div>
                        @error('parent_id')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </fieldset>

                    <div class="col-md-4 px-0">
                        <label class="form-label" for="category-order">ترتیب بین هم‌سطح‌ها</label>
                        <input
                            type="number"
                            name="sort_order"
                            id="category-order"
                            min="0"
                            value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                            class="form-control @error('sort_order') is-invalid @enderror"
                            @error('sort_order') aria-invalid="true" aria-describedby="category-order-help category-order-error" @else aria-describedby="category-order-help" @enderror
                        >
                        <div class="form-text" id="category-order-help">عدد کوچک‌تر در فهرست بالاتر می‌آید.</div>
                        @error('sort_order')
                            <div class="invalid-feedback d-block" id="category-order-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">توضیح و تصویر</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="category-description">توضیح</label>
                        <textarea
                            name="description"
                            id="category-description"
                            rows="3"
                            maxlength="2000"
                            class="form-control @error('description') is-invalid @enderror"
                            @error('description') aria-invalid="true" aria-describedby="category-description-error" @enderror
                        >{{ old('description', $category->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback" id="category-description-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label" for="category-image">تصویر</label>
                        <input
                            type="text"
                            name="image"
                            id="category-image"
                            value="{{ old('image', $category->image) }}"
                            class="form-control @error('image') is-invalid @enderror"
                            dir="ltr"
                            maxlength="500"
                            placeholder="https://"
                            autocomplete="off"
                            @error('image') aria-invalid="true" aria-describedby="category-image-help category-image-error" @else aria-describedby="category-image-help" @enderror
                        >
                        <div class="form-text" id="category-image-help">آدرس تصویر را بگذارید. اگر خالی باشد، دسته بدون تصویر ذخیره می‌شود.</div>
                        @error('image')
                            <div class="invalid-feedback d-block" id="category-image-error">{{ $message }}</div>
                        @enderror
                        <img class="cat-image-preview mt-3 d-none" id="category-image-preview" alt="پیش‌نمایش تصویر دسته">
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">سئو</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-baseline gap-2">
                            <label class="form-label" for="category-meta-title">عنوان سئو</label>
                            <span class="text-muted small"><span data-count-for="category-meta-title">0</span> / 200</span>
                        </div>
                        <input
                            type="text"
                            name="meta_title"
                            id="category-meta-title"
                            value="{{ old('meta_title', $category->meta_title) }}"
                            class="form-control @error('meta_title') is-invalid @enderror"
                            maxlength="200"
                            @error('meta_title') aria-invalid="true" aria-describedby="category-meta-title-error" @enderror
                        >
                        @error('meta_title')
                            <div class="invalid-feedback" id="category-meta-title-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <div class="d-flex justify-content-between align-items-baseline gap-2">
                            <label class="form-label" for="category-meta-description">توضیح سئو</label>
                            <span class="text-muted small"><span data-count-for="category-meta-description">0</span> / 500</span>
                        </div>
                        <textarea
                            name="meta_description"
                            id="category-meta-description"
                            rows="3"
                            maxlength="500"
                            class="form-control @error('meta_description') is-invalid @enderror"
                            @error('meta_description') aria-invalid="true" aria-describedby="category-meta-description-help category-meta-description-error" @else aria-describedby="category-meta-description-help" @enderror
                        >{{ old('meta_description', $category->meta_description) }}</textarea>
                        <div class="form-text" id="category-meta-description-help">اگر خالی بماند، توضیح دسته در نتایج جستجو استفاده نمی‌شود.</div>
                        @error('meta_description')
                            <div class="invalid-feedback d-block" id="category-meta-description-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="cat-form-side">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">وضعیت</h5></div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="category-active" role="switch" @checked($active)>
                            <label class="form-check-label" for="category-active">در سایت نشان داده شود</label>
                        </div>
                        <p class="text-muted small mb-3" id="category-active-note">
                            {{ $active ? 'این دسته در فیلتر سایت دیده می‌شود.' : 'این دسته در سایت پنهان است و فقط در مدیریت می‌ماند.' }}
                        </p>
                        <div class="cat-preview mb-3">
                            <div class="text-muted small mb-1">مسیر فیلتر</div>
                            <div class="cat-preview__url fw-medium" data-path-preview></div>
                        </div>
                        <a href="#" class="small d-none" id="category-public-link" target="_blank" rel="noopener">مشاهده در سایت</a>
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary" data-submit>{{ $category->exists ? 'ذخیرهٔ تغییرات' : 'افزودن دسته' }}</button>
                            <a href="{{ route('admin.categories.index', ['type' => $type]) }}" class="btn btn-label-secondary">انصراف</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    (function () {
        const form = document.getElementById('category-form');
        const parentList = document.getElementById('parent-list');
        const slug = document.querySelector('[data-slug]');
        const preview = document.querySelector('[data-path-preview]');
        const name = document.getElementById('category-name');
        const filter = document.getElementById('parent-filter');
        const empty = document.getElementById('parent-empty');
        const image = document.getElementById('category-image');
        const imagePreview = document.getElementById('category-image-preview');
        const active = document.getElementById('category-active');
        const activeNote = document.getElementById('category-active-note');
        const publicLink = document.getElementById('category-public-link');
        const publicBase = form.dataset.publicBase;
        let slugTouched = slug.value.trim() !== '';

        slug.addEventListener('input', function () {
            slugTouched = slug.value.trim() !== '';
            renderPath();
        });

        document.getElementById('slug-from-name').addEventListener('click', function () {
            slug.value = latinSlug(name.value);
            slugTouched = slug.value.trim() !== '';
            slug.focus();
            renderPath();
        });

        name.addEventListener('input', function () {
            if (slugTouched) return;
            slug.value = latinSlug(name.value);
            renderPath();
        });

        parentList.addEventListener('change', renderPath);

        filter.addEventListener('input', function () {
            const query = filter.value.trim().toLowerCase();
            let visible = 0;
            parentList.querySelectorAll('.cat-pick__row').forEach(function (row) {
                const checked = row.querySelector('input').checked;
                const matches = query === '' || checked || (row.dataset.search || '').includes(query);
                row.classList.toggle('d-none', !matches);
                if (matches) visible += 1;
            });
            empty.classList.toggle('d-none', visible !== 0);
        });

        active.addEventListener('change', function () {
            activeNote.textContent = active.checked
                ? 'این دسته در فیلتر سایت دیده می‌شود.'
                : 'این دسته در سایت پنهان است و فقط در مدیریت می‌ماند.';
            renderPath();
        });

        image.addEventListener('input', renderImage);
        imagePreview.addEventListener('error', function () {
            imagePreview.classList.add('d-none');
        });

        form.querySelectorAll('[data-count-for]').forEach(function (counter) {
            const field = document.getElementById(counter.dataset.countFor);
            const paint = function () { counter.textContent = String(field.value.length); };
            field.addEventListener('input', paint);
            paint();
        });

        form.addEventListener('submit', function () {
            const button = form.querySelector('[data-submit]');
            button.disabled = true;
            button.textContent = 'در حال ذخیره…';
        });

        function renderPath() {
            const selected = parentList.querySelector('input:checked');
            const base = selected ? (selected.dataset.path || '') : '';
            const piece = slug.value.trim();
            const path = [base, piece].filter(Boolean).join('/');
            preview.textContent = path ? '/' + path : '/';
            if (!path) {
                publicLink.classList.add('d-none');
                return;
            }
            publicLink.href = publicBase + '?category=' + encodeURIComponent(path);
            publicLink.classList.toggle('d-none', form.dataset.exists !== '1' || !active.checked);
        }

        function renderImage() {
            const url = image.value.trim();
            if (!url) {
                imagePreview.removeAttribute('src');
                imagePreview.classList.add('d-none');
                return;
            }
            imagePreview.src = url;
            imagePreview.classList.remove('d-none');
        }

        function latinSlug(value) {
            const map = {'ا':'a','آ':'a','ب':'b','پ':'p','ت':'t','ث':'s','ج':'j','چ':'ch','ح':'h','خ':'kh','د':'d','ذ':'z','ر':'r','ز':'z','ژ':'zh','س':'s','ش':'sh','ص':'s','ض':'z','ط':'t','ظ':'z','ع':'a','غ':'gh','ف':'f','ق':'gh','ک':'k','ك':'k','گ':'g','ل':'l','م':'m','ن':'n','و':'v','ه':'h','ی':'y','ي':'y','ئ':'y',' ':'-','۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9'};
            let out = '';
            for (const ch of value.trim()) out += map[ch] ?? ch;
            return out.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        }

        renderPath();
        renderImage();
    })();
</script>
@endsection
