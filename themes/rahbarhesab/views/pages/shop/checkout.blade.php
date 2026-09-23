@extends('theme::layouts.site')

@section('page')
@php
    $itemCount = $items->sum('quantity');
    $payable = (int) ($total ?? $subtotal);
    $isFreeCheckout = $payable <= 0;
    $gateway = config('cms.payment_gateway', 'zibal');
    $gatewayLabel = $gateway === 'zarinpal' ? 'زرین‌پال' : 'زیبال';
    $user = auth()->user();
    $typeLabels = [
        \App\Models\ShopProduct::TYPE_COURSE => 'دوره آموزشی',
        \App\Models\ShopProduct::TYPE_DIGITAL => 'محصول دیجیتال',
        \App\Models\ShopProduct::TYPE_PHYSICAL => 'محصول فیزیکی',
        \App\Models\ShopProduct::TYPE_BUNDLE => 'بسته آموزشی',
    ];
@endphp

<section class="rh-cart rh-cart--checkout" aria-labelledby="rh-checkout-title">
    <div class="rh-cart__glow" aria-hidden="true"></div>

    <header class="rh-cart__intro">
        <div class="rh-cart__intro-copy">
            <p class="rh-cart__kicker">پرداخت امن</p>
            <h1 id="rh-checkout-title" class="rh-cart__title">تسویه حساب</h1>
            <p class="rh-cart__lede">
                سفارش را مرور کنید و پرداخت را تکمیل کنید. بعد از تأیید، دسترسی دوره‌ها در پنل فعال می‌شود.
            </p>
        </div>

        <ol class="rh-cart__steps" aria-label="مراحل خرید">
            <li class="rh-cart__step is-done">
                <span class="rh-cart__step-num" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </span>
                <span>سبد</span>
            </li>
            <li class="rh-cart__step is-current" aria-current="step">
                <span class="rh-cart__step-num" aria-hidden="true">۲</span>
                <span>پرداخت</span>
            </li>
            <li class="rh-cart__step">
                <span class="rh-cart__step-num" aria-hidden="true">۳</span>
                <span>دسترسی</span>
            </li>
        </ol>
    </header>

    @if (session('success'))
        <div class="rh-cart__alert rh-cart__alert--ok" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="rh-cart__alert rh-cart__alert--error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="rh-cart__layout">
        <div class="rh-cart__main">
            <div class="rh-cart__sheet">
                <div class="rh-cart__spine" aria-hidden="true"><span></span><span></span><span></span></div>

                <div class="rh-cart__sheet-head">
                    <h2 class="rh-cart__sheet-title">مرور سفارش</h2>
                    <p class="rh-cart__sheet-hint">{{ fa_digits($itemCount) }} مورد</p>
                </div>

                <ul class="rh-cart__list">
                    @foreach ($items as $item)
                        @php
                            $product = $item->product;
                            $unitPrice = $product->effectivePrice();
                            $lineTotal = $unitPrice * $item->quantity;
                            $hasDiscount = $product->sale_price && $product->price > $product->sale_price;
                            $typeLabel = $typeLabels[$product->type] ?? 'محصول';
                            $detailUrl = $product->isCourse() || $product->isBundle()
                                ? route('courses.show', $product->slug)
                                : null;
                            $image = $product->featured_image;
                        @endphp
                        <li class="rh-cart__item">
                            <div class="rh-cart__thumb{{ $image ? '' : ' rh-cart__thumb--fallback' }}">
                                @if ($image)
                                    <img src="{{ $image }}" alt="" loading="lazy" width="96" height="72">
                                @else
                                    <span aria-hidden="true">{{ mb_substr($product->title, 0, 1) }}</span>
                                @endif
                            </div>

                            <div class="rh-cart__item-body">
                                <div class="rh-cart__item-top">
                                    <div>
                                        <p class="rh-cart__item-type">{{ $typeLabel }}</p>
                                        @if ($detailUrl)
                                            <a href="{{ $detailUrl }}" class="rh-cart__item-title">{{ $product->title }}</a>
                                        @else
                                            <h3 class="rh-cart__item-title">{{ $product->title }}</h3>
                                        @endif
                                        @if ($product->subtitle)
                                            <p class="rh-cart__item-sub">{{ $product->subtitle }}</p>
                                        @endif
                                    </div>
                                    @if ($item->quantity > 1)
                                        <span class="rh-cart__qty-badge">×{{ fa_digits($item->quantity) }}</span>
                                    @endif
                                </div>

                                <div class="rh-cart__item-meta">
                                    <p class="rh-cart__access-note">فعال‌سازی پس از تأیید پرداخت</p>
                                    <div class="rh-cart__item-price">
                                        @if ($hasDiscount)
                                            <del class="rh-cart__price-old">{{ fa_digits(number_format($product->price * $item->quantity)) }} تومان</del>
                                        @endif
                                        <span class="rh-cart__price-now">{{ fa_digits(number_format($lineTotal)) }} تومان</span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="rh-cart__continue">
                    <a href="{{ route('cart.index') }}" class="rh-cart__link-back">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        بازگشت به سبد خرید
                    </a>
                </div>
            </div>

            @if ($user)
                <div class="rh-cart__account">
                    <div class="rh-cart__account-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    </div>
                    <div class="rh-cart__account-copy">
                        <h2 class="rh-cart__account-title">حساب پرداخت‌کننده</h2>
                        <p class="rh-cart__account-name">{{ $user->displayName() }}</p>
                        <p class="rh-cart__account-meta">
                            @if ($user->mobile ?: $user->phone)
                                <span dir="ltr">{{ fa_digits($user->mobile ?: $user->phone) }}</span>
                            @endif
                            @if (($user->mobile ?: $user->phone) && $user->email)
                                <span class="rh-cart__account-dot" aria-hidden="true">·</span>
                            @endif
                            @if ($user->email)
                                <span dir="ltr">{{ $user->email }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <aside class="rh-cart__aside" aria-labelledby="rh-checkout-summary-title">
            <div class="rh-cart__summary">
                <h2 id="rh-checkout-summary-title" class="rh-cart__summary-title">پرداخت نهایی</h2>

                <dl class="rh-cart__totals">
                    <div class="rh-cart__totals-row">
                        <dt>جمع اقلام</dt>
                        <dd>{{ fa_digits(number_format($subtotal)) }} تومان</dd>
                    </div>

                    @if ($coupon ?? null)
                        <div class="rh-cart__totals-row rh-cart__totals-row--discount">
                            <dt>
                                تخفیف
                                <span class="rh-cart__coupon-tag">{{ $coupon->code }}</span>
                            </dt>
                            <dd>−{{ fa_digits(number_format($discount)) }} تومان</dd>
                        </div>
                    @endif

                    <div class="rh-cart__totals-row rh-cart__totals-row--due">
                        <dt>مبلغ قابل پرداخت</dt>
                        <dd>
                            @if ($isFreeCheckout)
                                رایگان
                            @else
                                {{ fa_digits(number_format($payable)) }} تومان
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($coupon ?? null)
                    <form method="POST" action="{{ route('cart.coupon.remove') }}" class="rh-cart__coupon-applied">
                        @csrf
                        @method('DELETE')
                        <p>کد <strong>{{ $coupon->code }}</strong> اعمال شده است.</p>
                        <button type="submit" class="rh-cart__coupon-remove">حذف کد</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('cart.coupon') }}" class="rh-cart__coupon" novalidate>
                        @csrf
                        <label for="rh-checkout-coupon" class="rh-cart__coupon-label">کد تخفیف دارید؟</label>
                        <div class="rh-cart__coupon-row">
                            <input
                                id="rh-checkout-coupon"
                                type="text"
                                name="code"
                                placeholder="مثلاً RAHBAR20"
                                autocomplete="off"
                                dir="ltr"
                                maxlength="50"
                                required
                            >
                            <button type="submit" class="rh-cart__coupon-apply">اعمال</button>
                        </div>
                    </form>
                @endif

                @unless ($isFreeCheckout)
                    <div class="rh-cart__gateway">
                        <p class="rh-cart__gateway-label">درگاه پرداخت</p>
                        <div class="rh-cart__gateway-card" aria-hidden="false">
                            <span class="rh-cart__gateway-badge">{{ $gatewayLabel }}</span>
                            <p class="rh-cart__gateway-hint">به درگاه بانکی امن منتقل می‌شوید.</p>
                        </div>
                    </div>
                @endunless

                <form method="POST" action="{{ route('checkout.process') }}" class="rh-cart__pay-form" data-rh-checkout-pay>
                    @csrf
                    <input type="hidden" name="gateway" value="{{ $gateway }}">
                    <button type="submit" class="rh-cart__cta rh-cart__cta--primary rh-cart__cta--block">
                        @if ($isFreeCheckout)
                            تکمیل ثبت‌نام رایگان
                        @else
                            پرداخت امن با {{ $gatewayLabel }}
                        @endif
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                    </button>
                </form>

                <ul class="rh-cart__trust">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                        اتصال امن به درگاه بانکی
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                        دسترسی فوری بعد از تأیید پرداخت
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                        اگر پرداخت لغو شود، سبد شما حفظ می‌ماند
                    </li>
                </ul>
            </div>
        </aside>
    </div>
</section>
@endsection
