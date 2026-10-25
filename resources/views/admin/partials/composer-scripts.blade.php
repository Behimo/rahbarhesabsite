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
        if (page === 1) {
            status.textContent = 'در حال بارگذاری...';
            grid.innerHTML = '';
        }
        grid.querySelector('[data-media-more]')?.remove();
        const url = new URL(mediaIndexUrl, window.location.origin);
        url.searchParams.set('json', '1');
        url.searchParams.set('page', String(page));
        try {
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) {
                throw new Error('media');
            }
            const data = await res.json();
            status.textContent = (data.data?.length || grid.children.length) ? '' : 'رسانه‌ای یافت نشد.';
            (data.data || []).forEach((item) => {
                if (!item.is_image) return;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'media-picker-item';
                const img = document.createElement('img');
                img.alt = '';
                img.src = item.url;
                const caption = document.createElement('div');
                caption.className = 'small text-truncate mt-1';
                caption.textContent = item.filename || '';
                btn.append(img, caption);
                btn.addEventListener('click', () => {
                    grid.querySelectorAll('.media-picker-item').forEach(el => el.classList.remove('is-selected'));
                    btn.classList.add('is-selected');
                    selectedUrl = item.url;
                });
                grid.appendChild(btn);
            });
            if (data.next_page_url) {
                const more = document.createElement('button');
                more.type = 'button';
                more.dataset.mediaMore = '1';
                more.className = 'btn btn-sm btn-outline-secondary w-100 mt-2';
                more.textContent = 'موارد بیشتر';
                more.addEventListener('click', () => loadMedia(page + 1));
                grid.appendChild(more);
            }
        } catch (error) {
            status.textContent = 'بارگذاری رسانه‌ها ناموفق بود.';
        }
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

        document.querySelectorAll('[data-media-target][data-media-preview]').forEach((btn) => {
            const input = document.querySelector(btn.getAttribute('data-media-target'));
            const preview = document.querySelector(btn.getAttribute('data-media-preview'));
            input?.addEventListener('input', () => {
                if (!preview) return;
                if (input.value) {
                    preview.src = input.value;
                    preview.classList.remove('d-none');
                } else {
                    preview.classList.add('d-none');
                }
            });
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
});
</script>
