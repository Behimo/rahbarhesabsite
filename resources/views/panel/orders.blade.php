@extends('theme::layouts.panel')

@section('title', 'سفارش‌ها')

@section('content')
<h1 class="panel-title">سفارش‌ها</h1>

@if ($orders->isEmpty())
    <p class="panel-empty">سفارشی ثبت نشده است.</p>
@else
    <div class="panel-table-wrap">
        <table class="panel-table">
            <thead>
                <tr>
                    <th>شماره</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ fa_digits(number_format($order->total)) }} تومان</td>
                        <td>
                            <span class="panel-status {{ $order->isPaid() ? 'panel-status--ok' : 'panel-status--wait' }}">
                                {{ $order->status === 'paid' ? 'پرداخت شده' : 'در انتظار' }}
                            </span>
                        </td>
                        <td>{{ fa_date($order->created_at) }}</td>
                        <td>
                            @if ($order->canRetryPayment())
                                <form method="POST" action="{{ route('checkout.retry', $order) }}">
                                    @csrf
                                    <button type="submit" class="panel-inline-link">پرداخت مجدد</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-pagination">{{ $orders->links() }}</div>
@endif
@endsection
