@extends('layouts.site')

@section('page')
<x-page-hero title="سبد خرید" />

<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-500/20 p-3 text-green-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-500/20 p-3 text-red-200">{{ session('error') }}</div>
    @endif

    @if ($items->isEmpty())
        <p class="text-center text-gray-400">سبد خرید خالی است.</p>
        <div class="mt-4 text-center">
            <a href="{{ route('courses.index') }}" class="btn-demo inline-flex">مشاهده دوره‌ها</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($items as $item)
                <div class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 p-4">
                    <div>
                        <h3 class="font-medium text-white">{{ $item->product->title }}</h3>
                        <p class="text-orange-400">{{ number_format($item->product->effectivePrice()) }} تومان</p>
                    </div>
                    <form method="POST" action="{{ route('cart.remove', $item) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-400 hover:underline">حذف</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex items-center justify-between rounded-xl border border-white/10 bg-white/5 p-4">
            <span class="text-gray-300">جمع کل:</span>
            <span class="text-xl font-bold text-white">{{ number_format($subtotal) }} تومان</span>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('checkout.index') }}" class="btn-demo inline-flex">تسویه حساب</a>
        </div>
    @endif
</div>
@endsection
