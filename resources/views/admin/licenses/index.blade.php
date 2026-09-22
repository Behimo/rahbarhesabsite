@extends('layouts.admin')

@section('title', 'لایسنس‌های اسپات‌پلیر')

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

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="mb-1">لایسنس‌های اسپات‌پلیر</h4>
        <p class="text-muted mb-0">صدور، وضعیت و پیگیری دسترسی دوره‌ها</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card">
            <div class="card-body py-3">
                <div class="text-muted small">همه</div>
                <div class="fs-4 fw-bold">{{ fa_digits($counts['all']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card">
            <div class="card-body py-3">
                <div class="text-muted small">صادر شده</div>
                <div class="fs-4 fw-bold text-success">{{ fa_digits($counts['issued']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card">
            <div class="card-body py-3">
                <div class="text-muted small">در انتظار</div>
                <div class="fs-4 fw-bold text-warning">{{ fa_digits($counts['pending']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card">
            <div class="card-body py-3">
                <div class="text-muted small">ناموفق</div>
                <div class="fs-4 fw-bold text-danger">{{ fa_digits($counts['failed']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.licenses.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">جستجو</label>
                <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="نام، موبایل، ایمیل، لایسنس، عنوان دوره">
            </div>
            <div class="col-md-3">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-select">
                    <option value="">همه وضعیت‌ها</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">دوره</label>
                <select name="course_id" class="form-select">
                    <option value="">همه دوره‌ها</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected((string) $courseId === (string) $course->id)>
                            {{ $course->product?->title ?? ('دوره #'.$course->id) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">فیلتر</button>
                <a href="{{ route('admin.licenses.index') }}" class="btn btn-label-secondary">پاک</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>کاربر</th>
                    <th>دوره</th>
                    <th>وضعیت</th>
                    <th>لایسنس</th>
                    <th>سفارش</th>
                    <th>تاریخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($licenses as $license)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $license->user?->name ?? '—' }}</div>
                            <div class="text-muted small" dir="ltr">{{ $license->user?->mobile ?: ($license->user?->phone ?? '') }}</div>
                        </td>
                        <td>{{ $license->course?->product?->title ?? ('#'.$license->course_id) }}</td>
                        <td>
                            <span class="badge {{ $statusBadges[$license->status] ?? 'bg-label-secondary' }}">
                                {{ $statusLabels[$license->status] ?? $license->status }}
                            </span>
                        </td>
                        <td dir="ltr" class="small">{{ $license->license_key ?: '—' }}</td>
                        <td>
                            @if ($license->order_id)
                                <a href="{{ route('admin.orders.show', $license->order_id) }}">#{{ $license->order_id }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="small">{{ $license->created_at?->format('Y/m/d H:i') }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.licenses.show', $license) }}" class="btn btn-sm btn-label-primary">جزئیات</a>
                            <form method="POST" action="{{ route('admin.licenses.reissue', $license) }}" class="d-inline" onsubmit="return confirm('صدور مجدد لایسنس انجام شود؟')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-label-warning">صدور مجدد</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">لایسنسی پیدا نشد</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($licenses->hasPages())
        <div class="card-footer">{{ $licenses->links() }}</div>
    @endif
</div>
@endsection
