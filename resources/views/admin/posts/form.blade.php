@extends('layouts.admin')

@section('title', $post->exists ? 'ویرایش مقاله' : 'مقاله جدید')

@section('vendor-style')
<style>
    .media-picker-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: .75rem; max-height: 360px; overflow: auto; }
    .media-picker-item { border: 1px solid #e4e4ec; border-radius: .5rem; padding: .35rem; cursor: pointer; background: #fff; text-align: center; }
    .media-picker-item.is-selected, .media-picker-item:hover { border-color: #643abc; box-shadow: 0 0 0 2px rgba(100,58,188,.15); }
    .media-picker-item img { width: 100%; height: 80px; object-fit: cover; border-radius: .35rem; }
    .featured-preview { max-width: 280px; border-radius: .75rem; border: 1px solid #e4e4ec; }
    .tag-check-grid { display: flex; flex-wrap: wrap; gap: .5rem .75rem; }
    .ck-editor__editable { min-height: 420px; direction: rtl; text-align: right; font-family: Vazirmatn, Tahoma, sans-serif; line-height: 1.9; }
    .ck.ck-editor { width: 100%; }
</style>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-3">
    <div>
        <a href="{{ route('admin.posts.index') }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت</a>
        <h4 class="mt-2 mb-0">{{ $post->exists ? 'ویرایش: '.$post->title : 'مقاله جدید' }}</h4>
    </div>
    @if ($post->exists)
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.posts.preview', $post) }}" class="btn btn-label-secondary" target="_blank" rel="noopener">پیش‌نمایش</a>
            @if ($post->isLive())
                <a href="{{ route('blog.show', $post->slug) }}" class="btn btn-label-primary" target="_blank" rel="noopener">مشاهده در سایت</a>
            @endif
            <a href="{{ route('admin.posts.revisions', $post) }}" class="btn btn-label-secondary">تاریخچه</a>
            <form method="POST" action="{{ route('admin.posts.duplicate', $post) }}">
                @csrf
                <button class="btn btn-label-info">کپی مقاله</button>
            </form>
        </div>
    @endif
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}" id="post-form">
    @csrf
    @if ($post->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">محتوا</h5></div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label">عنوان *</label>
                            <input type="text" name="title" id="post-title" value="{{ old('title', $post->title) }}" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Slug</label>
                            <div class="input-group">
                                <input type="text" name="slug" id="post-slug" value="{{ old('slug', $post->slug) }}" class="form-control" dir="ltr" placeholder="خودکار از عنوان">
                                <button type="button" class="btn btn-outline-secondary" id="slug-from-title" title="ساخت از عنوان">از عنوان</button>
                            </div>
                            <div class="form-text">اگر خالی بماند، از عنوان ساخته می‌شود.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">خلاصه</label>
                        <textarea name="excerpt" rows="2" class="form-control" maxlength="500">{{ old('excerpt', $post->excerpt) }}</textarea>
                    </div>

                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">متن مقاله</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="ckeditor-media-btn">درج از رسانه</button>
                        </div>
                        <textarea name="body" id="post-body" rows="18" class="form-control">{{ old('body', $post->body) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">سئو و اشتراک‌گذاری</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">عنوان سئو</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">توضیحات متا</label>
                        <textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description', $post->meta_description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">کلمات کلیدی</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $post->meta_keywords) }}" class="form-control">
                    </div>
                    <div>
                        <label class="form-label">تصویر Open Graph</label>
                        <div class="input-group">
                            <input type="text" name="og_image" id="og-image-input" value="{{ old('og_image', $post->og_image) }}" class="form-control" dir="ltr">
                            <button type="button" class="btn btn-outline-primary" data-media-target="#og-image-input">انتخاب</button>
                        </div>
                        <div class="form-text">اگر خالی باشد، تصویر شاخص استفاده می‌شود.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">انتشار</h5></div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published" @checked(old('is_published', $post->is_published))>
                        <label class="form-check-label" for="is_published">منتشر شود</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وضعیت</label>
                        <select name="status" id="post-status" class="form-select">
                            @foreach ([
                                'draft' => 'پیش‌نویس',
                                'published' => 'منتشر شده',
                                'scheduled' => 'زمان‌بندی‌شده',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $post->status ?: 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">برای نمایش در سایت بلاگ، «منتشر شود» را فعال کنید.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاریخ انتشار</label>
                        <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="form-control" dir="ltr">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">نویسنده</label>
                        <input type="text" name="author" value="{{ old('author', $post->author ?? config('cms.site_name_fa', 'راهبر حساب')) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">دسته‌بندی</label>
                        <select name="category_id" class="form-select">
                            <option value="">بدون دسته</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id', $post->category_id) == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text"><a href="{{ route('admin.categories.index') }}">مدیریت دسته‌ها</a></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">برچسب‌ها</label>
                        <div class="tag-check-grid">
                            @forelse ($tags as $tag)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="term_ids[]" value="{{ $tag->id }}" id="tag-{{ $tag->id }}"
                                           @checked(in_array($tag->id, old('term_ids', $selectedTerms), true))>
                                    <label class="form-check-label" for="tag-{{ $tag->id }}">{{ $tag->name }}</label>
                                </div>
                            @empty
                                <span class="text-muted small">برچسبی نیست. از بخش Taxonomy بسازید.</span>
                            @endforelse
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">ذخیره مقاله</button>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">تصویر شاخص</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-media-target="#featured-image-input" data-media-preview="#featured-preview">انتخاب رسانه</button>
                </div>
                <div class="card-body">
                    <input type="text" name="featured_image" id="featured-image-input" value="{{ old('featured_image', $post->featured_image) }}" class="form-control mb-2" dir="ltr" placeholder="URL تصویر">
                    <input type="text" name="featured_image_alt" value="{{ old('featured_image_alt', $post->featured_image_alt) }}" class="form-control mb-3" placeholder="متن جایگزین تصویر">
                    <img id="featured-preview" class="featured-preview w-100 {{ old('featured_image', $post->featured_image) ? '' : 'd-none' }}"
                         src="{{ old('featured_image', $post->featured_image) }}" alt="">
                </div>
            </div>
        </div>
    </div>
</form>

@include('admin.partials.media-picker-modal')
@endsection

@section('page-script')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
window.RahbarMediaPicker = (function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const mediaIndexUrl = @json(route('admin.media.index'));
    const mediaStoreUrl = @json(route('admin.media.store'));
    let targetInput = null;
    let previewImg = null;
    let onSelectCb = null;
    let selectedUrl = null;
    let modal = null;

    const modalEl = document.getElementById('mediaPickerModal');
    const grid = document.getElementById('media-picker-grid');
    const status = document.getElementById('media-picker-status');
    const fileInput = document.getElementById('media-picker-file');
    const confirmBtn = document.getElementById('media-picker-confirm');

    function ensureModal() {
        if (!modal && window.bootstrap && modalEl) {
            modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return modal;
    }

    async function loadMedia(page = 1) {
        status.textContent = 'در حال بارگذاری...';
        grid.innerHTML = '';
        const url = new URL(mediaIndexUrl, window.location.origin);
        url.searchParams.set('json', '1');
        url.searchParams.set('page', String(page));
        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        status.textContent = data.data?.length ? '' : 'رسانه‌ای یافت نشد.';
        (data.data || []).forEach((item) => {
            if (!item.is_image) return;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'media-picker-item';
            btn.innerHTML = `<img src="${item.url}" alt=""><div class="small text-truncate mt-1">${item.filename}</div>`;
            btn.addEventListener('click', () => {
                grid.querySelectorAll('.media-picker-item').forEach(el => el.classList.remove('is-selected'));
                btn.classList.add('is-selected');
                selectedUrl = item.url;
            });
            grid.appendChild(btn);
        });
    }

    async function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        status.textContent = 'در حال آپلود...';
        const res = await fetch(mediaStoreUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        });
        const data = await res.json();
        if (!res.ok || !data.url) {
            status.textContent = 'آپلود ناموفق بود.';
            return;
        }
        selectedUrl = data.url;
        applySelection();
    }

    function applySelection() {
        if (!selectedUrl) return;
        if (targetInput) {
            targetInput.value = selectedUrl;
            targetInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (previewImg) {
            previewImg.src = selectedUrl;
            previewImg.classList.remove('d-none');
        }
        if (typeof onSelectCb === 'function') onSelectCb(selectedUrl);
        ensureModal()?.hide();
    }

    function open(options = {}) {
        targetInput = options.target ? document.querySelector(options.target) : null;
        previewImg = options.preview ? document.querySelector(options.preview) : null;
        onSelectCb = options.onSelect || null;
        selectedUrl = null;
        ensureModal()?.show();
        loadMedia();
    }

    function bindTriggers() {
        document.querySelectorAll('[data-media-target]').forEach((btn) => {
            btn.addEventListener('click', () => {
                open({
                    target: btn.getAttribute('data-media-target'),
                    preview: btn.getAttribute('data-media-preview'),
                });
            });
        });

        const featured = document.getElementById('featured-image-input');
        const preview = document.getElementById('featured-preview');
        featured?.addEventListener('input', () => {
            if (!preview) return;
            if (featured.value) {
                preview.src = featured.value;
                preview.classList.remove('d-none');
            } else {
                preview.classList.add('d-none');
            }
        });

        fileInput?.addEventListener('change', () => {
            if (fileInput.files?.[0]) uploadFile(fileInput.files[0]);
        });
        confirmBtn?.addEventListener('click', applySelection);
    }

    return { open, bindTriggers };
})();

document.addEventListener('DOMContentLoaded', () => {
    window.RahbarMediaPicker.bindTriggers();

    const persianMap = {
        'ا':'a','آ':'a','ب':'b','پ':'p','ت':'t','ث':'s','ج':'j','چ':'ch','ح':'h','خ':'kh',
        'د':'d','ذ':'z','ر':'r','ز':'z','ژ':'zh','س':'s','ش':'sh','ص':'s','ض':'z','ط':'t',
        'ظ':'z','ع':'a','غ':'gh','ف':'f','ق':'gh','ک':'k','ك':'k','گ':'g','ل':'l','م':'m',
        'ن':'n','و':'v','ه':'h','ی':'y','ي':'y','ئ':'y','‌':'-',' ':'-'
    };

    function slugify(text) {
        const converted = String(text || '').split('').map(ch => persianMap[ch] ?? ch).join('');
        return converted.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').replace(/-{2,}/g, '-');
    }

    const titleInput = document.getElementById('post-title');
    const slugInput = document.getElementById('post-slug');
    let slugTouched = @json((bool) $post->exists);

    slugInput?.addEventListener('input', () => { slugTouched = true; });
    document.getElementById('slug-from-title')?.addEventListener('click', () => {
        slugInput.value = slugify(titleInput.value);
        slugTouched = true;
    });
    titleInput?.addEventListener('input', () => {
        if (!slugTouched || !slugInput.value) {
            slugInput.value = slugify(titleInput.value);
        }
    });

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const mediaStoreUrl = @json(route('admin.media.store'));
    let postEditor = null;

    class MediaUploadAdapter {
        constructor(loader) {
            this.loader = loader;
        }

        upload() {
            return this.loader.file.then((file) => {
                const data = new FormData();
                data.append('file', file);

                return fetch(mediaStoreUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: data,
                }).then(async (res) => {
                    const json = await res.json();
                    if (!res.ok || !json.url) {
                        throw new Error(json.message || 'آپلود ناموفق بود');
                    }
                    return { default: json.url };
                });
            });
        }

        abort() {}
    }

    function MediaUploadAdapterPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => new MediaUploadAdapter(loader);
    }

    function insertImage(editor, url) {
        editor.model.change((writer) => {
            const imageElement = writer.createElement('imageBlock', { src: url });
            editor.model.insertContent(imageElement, editor.model.document.selection);
        });
    }

    ClassicEditor.create(document.querySelector('#post-body'), {
        extraPlugins: [MediaUploadAdapterPlugin],
        toolbar: {
            items: [
                'heading', '|',
                'bold', 'italic', 'link', '|',
                'bulletedList', 'numberedList', '|',
                'uploadImage', 'blockQuote', 'insertTable', '|',
                'undo', 'redo'
            ],
            shouldNotGroupWhenFull: true,
        },
        image: {
            toolbar: ['imageTextAlternative', '|', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side'],
        },
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
        },
    }).then((editor) => {
        postEditor = editor;
        editor.editing.view.change((writer) => {
            writer.setAttribute('dir', 'rtl', editor.editing.view.document.getRoot());
        });

        const mediaBtn = document.getElementById('ckeditor-media-btn');
        mediaBtn?.addEventListener('click', () => {
            window.RahbarMediaPicker.open({
                onSelect(url) {
                    insertImage(editor, url);
                }
            });
        });
    }).catch((err) => console.error(err));

    document.getElementById('post-form')?.addEventListener('submit', () => {
        if (postEditor) {
            document.querySelector('#post-body').value = postEditor.getData();
        }
    });

    const publishCheckbox = document.getElementById('is_published');
    const statusSelect = document.getElementById('post-status');
    if (publishCheckbox && statusSelect) {
        publishCheckbox.addEventListener('change', () => {
            if (publishCheckbox.checked && statusSelect.value === 'draft') {
                statusSelect.value = 'published';
            }
            if (!publishCheckbox.checked) {
                statusSelect.value = 'draft';
            }
        });
        statusSelect.addEventListener('change', () => {
            publishCheckbox.checked = statusSelect.value !== 'draft';
        });
    }
});
</script>
@endsection

