@extends('theme::layouts.site')

@section('page')
@php
    $isFree = (int) $order->total <= 0;
    $itemCount = $order->items->sum('quantity');
@endphp

<section class="rh-cart rh-cart--result rh-cart--success" aria-labelledby="rh-success-title">
    <div class="rh-cart__glow" aria-hidden="true"></div>

    <header class="rh-cart__intro">
        <div class="rh-cart__intro-copy">
            <p class="rh-cart__kicker">دسترسی آماده است</p>
            <h1 id="rh-success-title" class="rh-cart__title">پرداخت موفق</h1>
            <p class="rh-cart__lede">
                سفارش شما ثبت شد. دوره‌ها و لایسنس‌ها از پنل هنرجو در دسترس هستند.
            </p>
        </div>

        <ol class="rh-cart__steps" aria-label="مراحل خرید">
            <li class="rh-cart__step is-done">
                <span class="rh-cart__step-num" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </span>
                <span>سبد</span>
            </li>
            <li class="rh-cart__step is-done">
                <span class="rh-cart__step-num" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </span>
                <span>پرداخت</span>
            </li>
            <li class="rh-cart__step is-current" aria-current="step">
                <span class="rh-cart__step-num" aria-hidden="true">۳</span>
                <span>دسترسی</span>
            </li>
        </ol>
    </header>

    <div class="rh-result">
        <div class="rh-result__stamp rh-result__stamp--ok" aria-hidden="true">
            <span class="rh-result__stamp-ring"></span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        </div>

        <div class="rh-result__sheet">
            <div class="rh-cart__spine" aria-hidden="true"><span></span><span></span><span></span></div>

            <p class="rh-result__eyebrow">{{ $isFree ? 'ثبت‌نام رایگان' : 'رسید پرداخت' }}</p>
            <h2 class="rh-result__heading">سفارش با موفقیت تکمیل شد</h2>
            <p class="rh-result__text">شماره سفارش <strong dir="ltr">{{ $order->order_number }}</strong></p>

            <dl class="rh-result__meta">
                <div>
                    <dt>مبلغ</dt>
                    <dd>
                        @if ($isFree)
                            رایگان
                        @else
                            {{ fa_digits(number_format($order->total)) }} تومان
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>اقلام</dt>
                    <dd>{{ fa_digits($itemCount) }} مورد</dd>
                </div>
                @if ($order->paid_at)
                    <div>
                        <dt>زمان تأیید</dt>
                        <dd>{{ fa_date($order->paid_at, 'yyyy/MM/dd HH:mm') }}</dd>
                    </div>
                @endif
                @if ($order->payment?->ref_id)
                    <div>
                        <dt>کد پیگیری</dt>
                        <dd dir="ltr">{{ $order->payment->ref_id }}</dd>
                    </div>
                @endif
            </dl>

            @if ($order->items->isNotEmpty())
                <div class="rh-result__block">
                    <h3 class="rh-result__block-title">اقلام سفارش</h3>
                    <ul class="rh-result__items">
                        @foreach ($order->items as $item)
                            <li>
                                <span>{{ $item->title ?: ($item->product?->title ?? 'محصول') }}</span>
                                <span>{{ fa_digits(number_format((int) $item->price * (int) $item->quantity)) }} تومان</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (!empty($licenses) && $licenses->isNotEmpty())
                <div class="rh-result__block rh-result__block--licenses">
                    <h3 class="rh-result__block-title">لایسنس اسپات‌پلیر</h3>
                    <ul class="rh-result__licenses">
                        @foreach ($licenses as $license)
                            <li>
                                <div>
                                    <p class="rh-result__license-title">{{ $license->course->product->title ?? 'دوره' }}</p>
                                    @if ($license->license_key)
                                        <p class="rh-result__license-key" dir="ltr">{{ $license->license_key }}</p>
                                    @else
                                        <p class="rh-result__license-pending">در حال صدور… چند لحظه دیگر از پنل بررسی کنید.</p>
                                    @endif
                                </div>
                                @if ($license->spot_url)
                                    <a href="{{ $license->spot_url }}" class="rh-result__license-link" target="_blank" rel="noopener noreferrer">مشاهده در اسپات‌پلیر</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rh-result__actions">
                <a href="{{ route('panel.courses') }}" class="rh-cart__cta rh-cart__cta--primary">دوره‌های من</a>
                <a href="{{ route('panel.orders') }}" class="rh-result__btn-secondary">سفارش‌های من</a>
                <a href="{{ route('home') }}" class="rh-result__btn-quiet">بازگشت به خانه</a>
            </div>
        </div>

        <ul class="rh-cart__trust rh-result__trust">
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                رسید پرداخت در پنل سفارش‌ها ذخیره شده است
            </li>
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                اگر لایسنس هنوز آماده نیست، چند دقیقه بعد از «دوره‌های من» دوباره امتحان کنید
            </li>
        </ul>
    </div>
</section>
@endsection
