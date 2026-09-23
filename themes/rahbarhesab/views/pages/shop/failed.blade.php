@extends('theme::layouts.site')

@section('page')
@php
    $message = $message ?? session('error') ?? 'پرداخت ناموفق یا لغو شد. سبد خرید شما حفظ شده است.';
    $canRetry = $order && auth()->check() && $order->user_id === auth()->id() && $order->canRetryPayment();
    $gateway = config('cms.payment_gateway', 'zibal');
    $gatewayLabel = $gateway === 'zarinpal' ? 'زرین‌پال' : 'زیبال';
@endphp

<section class="rh-cart rh-cart--result rh-cart--failed" aria-labelledby="rh-failed-title">
    <div class="rh-cart__glow" aria-hidden="true"></div>

    <header class="rh-cart__intro">
        <div class="rh-cart__intro-copy">
            <p class="rh-cart__kicker">نیازی به نگرانی نیست</p>
            <h1 id="rh-failed-title" class="rh-cart__title">پرداخت ناموفق</h1>
            <p class="rh-cart__lede">
                مبلغی از حساب شما کسر نشده یا در صورت کسر، معمولاً ظرف چند ساعت برگشت داده می‌شود. می‌توانید دوباره تلاش کنید.
            </p>
        </div>

        <ol class="rh-cart__steps" aria-label="مراحل خرید">
            <li class="rh-cart__step is-done">
                <span class="rh-cart__step-num" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </span>
                <span>سبد</span>
            </li>
            <li class="rh-cart__step is-failed" aria-current="step">
                <span class="rh-cart__step-num" aria-hidden="true">۲</span>
                <span>پرداخت</span>
            </li>
            <li class="rh-cart__step">
                <span class="rh-cart__step-num" aria-hidden="true">۳</span>
                <span>دسترسی</span>
            </li>
        </ol>
    </header>

    <div class="rh-result">
        <div class="rh-result__stamp rh-result__stamp--fail" aria-hidden="true">
            <span class="rh-result__stamp-ring"></span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </div>

        <div class="rh-result__sheet">
            <div class="rh-cart__spine" aria-hidden="true"><span></span><span></span><span></span></div>

            <p class="rh-result__eyebrow rh-result__eyebrow--fail">پرداخت تکمیل نشد</p>
            <h2 class="rh-result__heading">هنوز دسترسی فعال نشده است</h2>
            <p class="rh-result__text">{{ $message }}</p>

            @if ($order)
                <dl class="rh-result__meta">
                    <div>
                        <dt>شماره سفارش</dt>
                        <dd dir="ltr">{{ $order->order_number }}</dd>
                    </div>
                    <div>
                        <dt>مبلغ</dt>
                        <dd>{{ fa_digits(number_format($order->total)) }} تومان</dd>
                    </div>
                    @if ($order->payment?->error_message)
                        <div class="rh-result__meta-wide">
                            <dt>جزئیات</dt>
                            <dd>{{ $order->payment->error_message }}</dd>
                        </div>
                    @endif
                </dl>
            @endif

            <div class="rh-result__actions">
                @if ($canRetry)
                    <form method="POST" action="{{ route('checkout.retry', $order) }}" class="rh-result__retry-form">
                        @csrf
                        <input type="hidden" name="gateway" value="{{ $gateway }}">
                        <button type="submit" class="rh-cart__cta rh-cart__cta--primary rh-cart__cta--block">
                            تلاش مجدد با {{ $gatewayLabel }}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
                        </button>
                    </form>
                @endif

                <a href="{{ route('cart.index') }}" class="{{ $canRetry ? 'rh-result__btn-secondary' : 'rh-cart__cta rh-cart__cta--primary' }}">
                    بازگشت به سبد خرید
                </a>
                <a href="{{ route('checkout.index') }}" class="rh-result__btn-quiet">رفتن به تسویه حساب</a>
                <a href="{{ route('home') }}" class="rh-result__btn-quiet">صفحه اصلی</a>
            </div>
        </div>

        <ul class="rh-cart__trust rh-result__trust">
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                سبد خرید شما حفظ شده است
            </li>
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                در صورت نیاز با پشتیبانی راهبر حساب تماس بگیرید
            </li>
        </ul>
    </div>
</section>
@endsection
