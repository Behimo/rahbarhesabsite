<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-labelledby="mediaPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaPickerModalLabel">انتخاب رسانه</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <label class="btn btn-outline-primary mb-0">
                        آپلود تصویر جدید
                        <input type="file" id="media-picker-file" accept="image/*" hidden>
                    </label>
                    <span id="media-picker-status" class="text-muted small align-self-center"></span>
                </div>
                <div id="media-picker-grid" class="media-picker-grid"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-primary" id="media-picker-confirm">انتخاب</button>
            </div>
        </div>
    </div>
</div>
