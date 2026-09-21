@extends('theme::layouts.site')

@section('page')
<div class="mx-auto max-w-lg px-4 py-16 text-center sm:px-6">
    <div class="rh-card border-green-200 bg-green-50 p-8">
        <div class="mb-4 text-5xl text-green-600">✓</div>
        <h1 class="mb-2 text-2xl font-bold text-slate-800">پرداخت موفق</h1>
        <p class="mb-4 text-slate-600">سفارش {{ $order->order_number }} با موفقیت ثبت شد.</p>
        <p class="mb-6 text-xl font-bold text-teal-700">{{ number_format($order->total) }} تومان</p>

        @if (!empty($licenses) && $licenses->isNotEmpty())
            <div class="mb-6 rounded-xl bg-white p-4 text-start text-sm">
                <p class="mb-2 font-semibold text-slate-800">لایسنس اسپات‌پلیر</p>
                @foreach ($licenses as $license)
                    <p class="mb-1 text-slate-600">{{ $license->course->product->title ?? 'دوره' }}:
                        @if ($license->license_key)
                            <span dir="ltr">{{ $license->license_key }}</span>
                        @else
                            در حال صدور...
                        @endif
                    </p>
                    @if ($license->spot_url)
                        <a href="{{ $license->spot_url }}" class="text-teal-700 underline" target="_blank">باز کردن در اپ</a>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="{{ route('panel.courses') }}" class="rh-btn-primary inline-flex justify-center">دوره‌های من</a>
            <a href="{{ route('home') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-6 py-3 text-slate-700 hover:bg-slate-50">بازگشت به خانه</a>
        </div>
    </div>
</div>
@endsection
