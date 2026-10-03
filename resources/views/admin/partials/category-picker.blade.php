@include('admin.partials.category-ui')

<div class="mb-3">
    <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
        <label class="form-label mb-0">دسته‌بندی</label>
        <a href="{{ route('admin.categories.index', ['type' => 'product']) }}" class="small">مدیریت درخت</a>
    </div>
    @if ($categories->isEmpty())
        <p class="text-muted small mb-0">دسته‌ای برای محصول نیست. <a href="{{ route('admin.categories.create', ['type' => 'product']) }}">اولین دسته را بسازید</a></p>
    @else
        @php
            $selectedIds = array_map('intval', (array) old('category_ids', $selectedCategoryIds ?? []));
            $primary = (string) old('primary_category_id', $primaryCategoryId ?? '');
        @endphp
        <div class="d-flex gap-3 small text-muted mb-1" style="padding-inline-start: .85rem;">
            <span>عضویت</span>
            <span>اصلی</span>
        </div>
        <div class="cat-pick" data-category-picker>
            @foreach ($categories as $category)
                <label class="cat-pick__row" style="padding-inline-start: {{ .85 + ($category->treeDepth * 1.15) }}rem">
                    <input class="form-check-input cat-check" type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                           @checked(in_array($category->id, $selectedIds, true))>
                    <input class="form-check-input cat-primary" type="radio" name="primary_category_id" value="{{ $category->id }}"
                           @checked($primary === (string) $category->id)
                           title="دسته اصلی">
                    <span>
                        <span class="cat-pick__name">{{ $category->name }}</span>
                        <span class="cat-path">{{ $category->full_slug }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        <div class="form-text">چک‌باکس عضویت در دسته است. دکمهٔ گرد، دستهٔ اصلی را برای مسیر صفحه مشخص می‌کند.</div>
    @endif
</div>

<script>
    document.querySelectorAll('[data-category-picker]').forEach(function (picker) {
        picker.addEventListener('change', function (event) {
            const row = event.target.closest('.cat-pick__row');
            if (!row) return;
            const box = row.querySelector('.cat-check');
            const radio = row.querySelector('.cat-primary');
            if (event.target === radio && radio.checked) {
                box.checked = true;
            }
            if (event.target === box && !box.checked && radio.checked) {
                radio.checked = false;
            }
            if (event.target === box && box.checked && !picker.querySelector('.cat-primary:checked')) {
                radio.checked = true;
            }
        });
    });
</script>
