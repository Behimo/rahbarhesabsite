@extends('theme::layouts.site')

@section('page')
<section class="bg-teal-800 py-12 text-white">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
        <h1 class="text-3xl font-extrabold">تسویه حساب</h1>
    </div>
</section>

<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
    <div class="mb-6 space-y-3">
        @foreach ($items as $item)
            <div class="rh-card flex justify-between p-4">
                <span class="text-slate-800">{{ $item->product->title }}</span>
                <span class="text-slate-600">{{ number_format($item->product->effectivePrice()) }} تومان</span>
            </div>
        @endforeach
    </div>

    <div class="rh-card mb-6 flex justify-between border-teal-200 bg-teal-50 p-4">
        <span class="font-semibold text-slate-800">جمع</span>
        <span class="text-slate-700">{{ number_format($subtotal) }} تومان</span>
    </div>

    @include('components.cart-totals')

    <form method="POST" action="{{ route('checkout.process') }}" class="mt-6">
        @csrf
        <button type="submit" class="rh-btn-primary w-full justify-center">پرداخت با {{ config('cms.payment_gateway') === 'zibal' ? 'زیبال' : 'زرین‌پال' }}</button>
    </form>
</div>
@endsection
