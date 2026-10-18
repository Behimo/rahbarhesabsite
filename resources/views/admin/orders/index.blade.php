@extends('layouts.admin')

@section('title', 'سفارش‌ها')

@section('lede', 'مدیریت سفارش‌ها و موارد نیازمند بررسی مهاجرت')

@section('actions')
    <div class="btn-group">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm {{ $filter === '' ? 'btn-primary' : 'btn-label-secondary' }}">همه</a>
        <a href="{{ route('admin.orders.index', ['filter' => 'needs_review']) }}" class="btn btn-sm {{ $filter === 'needs_review' ? 'btn-warning' : 'btn-label-warning' }}">نیاز به بررسی</a>
        <a href="{{ route('admin.orders.index', ['filter' => 'without_user']) }}" class="btn btn-sm {{ $filter === 'without_user' ? 'btn-danger' : 'btn-label-danger' }}">بدون کاربر</a>
    </div>
@endsection

@section('content')

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>شماره</th>
                    <th>کاربر</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td dir="ltr">
                            {{ $order->order_number }}
                            @if ($order->needsReview())
                                <span class="badge bg-label-warning ms-1">سفارش یتیم WP</span>
                            @endif
                        </td>
                        <td>
                            @if ($order->user)
                                {{ $order->user->displayName() }}
                            @else
                                <span class="text-danger">بدون کاربر</span>
                                @if ($order->billing_phone)
                                    <br><small dir="ltr">{{ $order->billing_phone }}</small>
                                @endif
                            @endif
                        </td>
                        <td>{{ number_format($order->total) }} تومان</td>
                        <td>
                            <span class="badge {{ $order->isPaid() ? 'bg-label-success' : 'bg-label-warning' }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td>{{ $order->created_at->format('Y/m/d H:i') }}</td>
                        <td><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-label-primary">جزئیات</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">سفارشی ثبت نشده</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($orders->hasPages())
        <div class="card-footer">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
