@extends('layouts.admin')

@section('title', 'سفارش‌ها')

@section('content')
<h4 class="mb-4">سفارش‌ها</h4>

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
                        <td dir="ltr">{{ $order->order_number }}</td>
                        <td>{{ $order->user->name }}</td>
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
