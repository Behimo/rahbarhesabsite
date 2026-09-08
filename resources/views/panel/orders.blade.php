@extends('layouts.panel')

@section('title', 'سفارش‌ها')

@section('content')
<h1 class="mb-6 text-2xl font-bold text-white">سفارش‌ها</h1>

@if ($orders->isEmpty())
    <p class="text-gray-400">سفارشی ثبت نشده است.</p>
@else
    <div class="overflow-hidden rounded-xl border border-white/10">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-gray-400">
                <tr>
                    <th class="px-4 py-3 text-start">شماره</th>
                    <th class="px-4 py-3 text-start">مبلغ</th>
                    <th class="px-4 py-3 text-start">وضعیت</th>
                    <th class="px-4 py-3 text-start">تاریخ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr class="border-t border-white/10">
                        <td class="px-4 py-3 text-white">{{ $order->order_number }}</td>
                        <td class="px-4 py-3 text-gray-300">{{ number_format($order->total) }} تومان</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs {{ $order->isPaid() ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300' }}">
                                {{ $order->status === 'paid' ? 'پرداخت شده' : 'در انتظار' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-400">{{ $order->created_at->format('Y/m/d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
@endif
@endsection
