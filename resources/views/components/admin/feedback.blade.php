@php
    $titles = [
        'success' => 'انجام شد',
        'danger' => 'خطا',
        'warning' => 'توجه',
        'info' => 'اطلاع',
    ];
    $icons = [
        'success' => 'ti-circle-check',
        'danger' => 'ti-alert-circle',
        'warning' => 'ti-alert-triangle',
        'info' => 'ti-info-circle',
    ];
    $grouped = [];

    foreach ($notices as $notice) {
        $grouped[$notice->level][] = $notice->message;
    }
@endphp

<div class="admin-feedback mb-4" id="admin-feedback">
    @foreach ($grouped as $level => $messages)
        <div class="alert alert-{{ $level }} alert-dismissible mb-3" role="{{ $level === 'danger' ? 'alert' : 'status' }}">
            <div class="d-flex gap-2">
                <i class="ti {{ $icons[$level] ?? 'ti-info-circle' }} fs-5"></i>
                <div>
                    <div class="fw-semibold mb-1">{{ $titles[$level] ?? 'پیام' }}</div>
                    @if (count($messages) === 1)
                        <div>{{ $messages[0] }}</div>
                    @else
                        <ul class="mb-0">
                            @foreach ($messages as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
        </div>
    @endforeach
</div>
