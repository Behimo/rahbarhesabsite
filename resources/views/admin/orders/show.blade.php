@extends('layouts.admin')

@section('title', 'جزئیات سفارش')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.orders.index') }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت</a>
    <h4 class="mt-2 mb-0">سفارش {{ $order->order_number }}</h4>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
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
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p><strong>کاربر:</strong> {{ $order->user->name }}</p>
                <p><strong>ایمیل:</strong> {{ $order->user->email }}</p>
                <p><strong>وضعیت:</strong> {{ $order->status }}</p>
                <p><strong>جمع:</strong> {{ number_format($order->total) }} تومان</p>
                @if ($order->payment)
                    <p><strong>درگاه:</strong> {{ $order->payment->gateway }}</p>
                    @if ($order->payment->ref_id)
                        <p><strong>کد پیگیری:</strong> {{ $order->payment->ref_id }}</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
