@extends('layouts.site')

@section('page')
<div class="mx-auto max-w-lg px-4 py-16 text-center sm:px-6">
    <div class="rounded-2xl border border-green-500/30 bg-green-500/10 p-8">
        <div class="mb-4 text-5xl">✓</div>
        <h1 class="mb-2 text-2xl font-bold text-white">پرداخت موفق</h1>
        <p class="mb-4 text-gray-300">سفارش {{ $order->order_number }} با موفقیت ثبت شد.</p>
        <p class="mb-6 text-orange-400">{{ number_format($order->total) }} تومان</p>
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="{{ route('panel.courses') }}" class="btn-demo inline-flex justify-center">دوره‌های من</a>
            <a href="{{ route('home') }}" class="inline-flex justify-center rounded-xl border border-white/10 px-6 py-3 text-white hover:bg-white/5">بازگشت به خانه</a>
        </div>
    </div>
</div>
@endsection
