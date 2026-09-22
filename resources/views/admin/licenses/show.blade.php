@extends('layouts.admin')

@section('title', 'جزئیات لایسنس')

@section('content')
@php
    $statusLabels = [
        'issued' => 'صادر شده',
        'pending' => 'در انتظار',
        'failed' => 'ناموفق',
    ];
    $statusBadges = [
        'issued' => 'bg-label-success',
        'pending' => 'bg-label-warning',
        'failed' => 'bg-label-danger',
    ];
@endphp

<div class="mb-4">
    <a href="{{ route('admin.licenses.index') }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت</a>
    <h4 class="mt-2 mb-0">لایسنس #{{ $license->id }}</h4>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <form method="POST" action="{{ route('admin.licenses.reissue', $license) }}" onsubmit="return confirm('صدور مجدد لایسنس انجام شود؟')">
        @csrf
        <button type="submit" class="btn btn-warning">صدور مجدد</button>
    </form>
    @if ($license->order_id)
        <a href="{{ route('admin.orders.show', $license->order_id) }}" class="btn btn-outline-primary">مشاهده سفارش</a>
    @endif
    @if ($license->course?->product)
        <a href="{{ route('admin.courses.edit', $license->course->product) }}" class="btn btn-outline-secondary">ویرایش دوره</a>
    @endif
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card mb-4">
            <div class="card-header">اطلاعات لایسنس</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">وضعیت</dt>
                    <dd class="col-sm-8">
                        <span class="badge {{ $statusBadges[$license->status] ?? 'bg-label-secondary' }}">
                            {{ $statusLabels[$license->status] ?? $license->status }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">کاربر</dt>
                    <dd class="col-sm-8">
                        {{ $license->user?->name ?? '—' }}
                        <div class="text-muted small" dir="ltr">
                            {{ $license->user?->mobile ?: ($license->user?->phone ?? '') }}
                            @if ($license->user?->email)
                                · {{ $license->user->email }}
                            @endif
                        </div>
                    </dd>

                    <dt class="col-sm-4">دوره</dt>
                    <dd class="col-sm-8">{{ $license->course?->product?->title ?? ('#'.$license->course_id) }}</dd>

                    <dt class="col-sm-4">SpotPlayer Course ID</dt>
                    <dd class="col-sm-8" dir="ltr">{{ $license->course?->spotplayer_course_id ?: '—' }}</dd>

                    <dt class="col-sm-4">کد لایسنس</dt>
                    <dd class="col-sm-8" dir="ltr">{{ $license->license_key ?: '—' }}</dd>

                    <dt class="col-sm-4">لینک اسپات‌پلیر</dt>
                    <dd class="col-sm-8">
                        @if ($license->spot_url)
                            <a href="{{ $license->spot_url }}" target="_blank" rel="noopener noreferrer" dir="ltr">{{ $license->spot_url }}</a>
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="col-sm-4">دستگاه‌ها</dt>
                    <dd class="col-sm-8">{{ fa_digits($license->device_count ?? 0) }} / {{ fa_digits($license->devices_limit ?? 0) }}</dd>

                    <dt class="col-sm-4">منبع صدور</dt>
                    <dd class="col-sm-8">{{ $license->issued_via ?: '—' }}</dd>

                    <dt class="col-sm-4">تاریخ صدور</dt>
                    <dd class="col-sm-8">{{ $license->issued_at?->format('Y/m/d H:i') ?: '—' }}</dd>

                    <dt class="col-sm-4">آخرین بررسی</dt>
                    <dd class="col-sm-8">{{ $license->last_verified_at?->format('Y/m/d H:i') ?: '—' }}</dd>

                    <dt class="col-sm-4">ایجاد</dt>
                    <dd class="col-sm-8">{{ $license->created_at?->format('Y/m/d H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header">پاسخ API / خطای تشخیصی</div>
            <div class="card-body">
                @if (! empty($license->api_response))
                    <pre class="mb-0 small bg-light p-3 rounded" style="max-height: 420px; overflow: auto; white-space: pre-wrap; direction: ltr; text-align: left;">{{ json_encode($license->api_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                @else
                    <p class="text-muted mb-0">پاسخی ذخیره نشده است.</p>
                @endif
                <p class="text-muted small mt-3 mb-0">لاگ جاب‌ها: <code dir="ltr">storage/logs/jobs.log</code></p>
            </div>
        </div>
    </div>
</div>
@endsection
