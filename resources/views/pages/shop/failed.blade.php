@extends('layouts.site')

@section('page')
<div class="mx-auto max-w-lg px-4 py-16 text-center sm:px-6">
    <div class="rounded-2xl border border-red-500/30 bg-red-500/10 p-8">
        <h1 class="mb-2 text-2xl font-bold text-white">پرداخت ناموفق</h1>
        <p class="mb-6 text-gray-300">{{ $message ?? 'پرداخت ناموفق یا لغو شد. سبد خرید شما حفظ شده است.' }}</p>
        <a href="{{ route('cart.index') }}" class="btn-demo inline-flex justify-center">بازگشت به سبد خرید</a>
    </div>
</div>
@endsection
