@extends('layouts.site')

@section('page')
<x-page-hero title="تسویه حساب" />

<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
    <div class="mb-6 space-y-3">
        @foreach ($items as $item)
            <div class="flex justify-between rounded-xl border border-white/10 bg-white/5 p-4">
                <span class="text-white">{{ $item->product->title }}</span>
                <span class="text-gray-300">{{ number_format($item->product->effectivePrice()) }} تومان</span>
            </div>
        @endforeach
    </div>

    <div class="mb-6 flex justify-between rounded-xl border border-orange-500/30 bg-orange-500/10 p-4">
        <span class="font-semibold text-white">جمع</span>
        <span class="text-orange-200">{{ number_format($subtotal) }} تومان</span>
    </div>
    @include('components.cart-totals')

    <form method="POST" action="{{ route('checkout.process') }}" class="mt-6">
        @csrf
        <button type="submit" class="btn-demo w-full justify-center">پرداخت با {{ config('cms.payment_gateway') === 'zibal' ? 'زیبال' : 'زرین‌پال' }}</button>
    </form>
</div>
@endsection
