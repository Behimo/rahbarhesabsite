@extends('layouts.admin')

@section('title', 'جزئیات سفارش')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.orders.index') }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت</a>
    <h4 class="mt-2 mb-0">سفارش {{ $order->order_number }}</h4>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex flex-wrap gap-2 mb-4">
    @unless ($order->isPaid())
        <form method="POST" action="{{ route('admin.orders.mark-paid', $order) }}" onsubmit="return confirm('سفارش پرداخت‌شده شود و دسترسی صادر گردد؟')">
            @csrf
            <button class="btn btn-success">علامت پرداخت دستی</button>
        </form>
    @endunless
    <form method="POST" action="{{ route('admin.orders.retry-licenses', $order) }}">
        @csrf
        <button class="btn btn-outline-primary">صدور مجدد لایسنس</button>
    </form>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">اقلام سفارش</h5>
                <table class="table">
                    <thead><tr><th>محصول</th><th>قیمت</th><th>تعداد</th></tr></thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>{{ $item->title }}</td>
                                <td>{{ number_format($item->price) }} تومان</td>
                                <td>{{ $item->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">ثبت‌نام دستی / هدیه</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.orders.enroll') }}" class="row g-3 align-items-end">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $order->user_id }}">
                    <div class="col-md-6">
                        <label class="form-label">دوره</label>
                        <select name="course_id" class="form-select" required>
                            @foreach (\App\Models\Course::query()->with('product')->get() as $course)
                                <option value="{{ $course->id }}">{{ $course->product->title ?? 'دوره #'.$course->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">منبع</label>
                        <select name="source" class="form-select">
                            <option value="manual">دستی</option>
                            <option value="gift">هدیه</option>
                            <option value="free">رایگان</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100">ثبت دسترسی</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">دسترسی‌های کاربر</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>دوره</th><th>منبع</th><th>وضعیت</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($enrollments as $enrollment)
                            <tr>
                                <td>{{ $enrollment->course->product->title ?? '-' }}</td>
                                <td>{{ $enrollment->source }}</td>
                                <td>{{ $enrollment->status }}</td>
                                <td>
                                    @if ($enrollment->status !== 'revoked')
                                        <form method="POST" action="{{ route('admin.orders.enrollments.revoke', $enrollment) }}" onsubmit="return confirm('لغو شود؟')">
                                            @csrf
                                            <button class="btn btn-sm btn-label-danger">لغو</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">ثبت‌نامی نیست</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <p><strong>کاربر:</strong> {{ $order->user->name }}</p>
                <p><strong>موبایل:</strong> {{ $order->user->mobile ?: $order->user->phone }}</p>
                <p><strong>وضعیت:</strong> {{ $order->status }}</p>
                <p><strong>جمع:</strong> {{ number_format($order->subtotal) }} تومان</p>
                <p><strong>تخفیف:</strong> {{ number_format($order->discount) }} تومان @if($order->coupon_code) ({{ $order->coupon_code }}) @endif</p>
                <p><strong>قابل پرداخت:</strong> {{ number_format($order->total) }} تومان</p>
                @if ($order->payment)
                    <p><strong>درگاه:</strong> {{ $order->payment->gateway }}</p>
                    @if ($order->payment->ref_id)
                        <p><strong>کد پیگیری:</strong> {{ $order->payment->ref_id }}</p>
                    @endif
                    @if ($order->payment->card_pan)
                        <p><strong>کارت:</strong> {{ $order->payment->card_pan }}</p>
                    @endif
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header">لایسنس اسپات‌پلیر</div>
            <div class="card-body">
                @forelse ($licenses as $license)
                    <p class="mb-2">
                        {{ $license->course->product->title ?? 'دوره' }} —
                        <span class="badge bg-label-{{ $license->status === 'issued' ? 'success' : 'warning' }}">{{ $license->status }}</span>
                        @if ($license->license_key)
                            <br><small dir="ltr">{{ $license->license_key }}</small>
                        @endif
                    </p>
                @empty
                    <p class="text-muted mb-0">لایسنسی ثبت نشده</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
