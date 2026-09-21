@extends('theme::layouts.site')

@section('page')
<section class="bg-teal-800 py-12 text-white">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
        <h1 class="text-3xl font-extrabold">سبد خرید</h1>
    </div>
</section>

<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-3 text-green-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-red-800">{{ session('error') }}</div>
    @endif

    @if ($items->isEmpty())
        <p class="text-center text-slate-500">سبد خرید خالی است.</p>
        <div class="mt-4 text-center">
            <a href="{{ route('courses.index') }}" class="rh-btn-primary inline-flex">مشاهده دوره‌ها</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($items as $item)
                <div class="rh-card flex items-center justify-between p-4">
                    <div>
                        <h3 class="font-semibold text-slate-800">{{ $item->product->title }}</h3>
                        <p class="rh-price">{{ number_format($item->product->effectivePrice()) }} تومان</p>
                    </div>
                    <form method="POST" action="{{ route('cart.remove', $item) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:underline">حذف</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="rh-card mt-6 flex items-center justify-between p-4">
            <span class="text-slate-600">جمع کل:</span>
            <span class="text-xl font-bold text-slate-800">{{ number_format($subtotal) }} تومان</span>
        </div>

        @include('components.cart-totals')

        <div class="mt-6 text-center">
            <a href="{{ route('checkout.index') }}" class="rh-btn-primary inline-flex">تسویه حساب</a>
        </div>
    @endif
</div>
@endsection
