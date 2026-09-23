@extends('theme::layouts.site')

@section('page')
@php
    $itemCount = $items->sum('quantity');
    $payable = (int) ($total ?? $subtotal);
    $typeLabels = [
        \App\Models\ShopProduct::TYPE_COURSE => 'دوره آموزشی',
        \App\Models\ShopProduct::TYPE_DIGITAL => 'محصول دیجیتال',
        \App\Models\ShopProduct::TYPE_PHYSICAL => 'محصول فیزیکی',
        \App\Models\ShopProduct::TYPE_BUNDLE => 'بسته آموزشی',
    ];
@endphp

<section class="rh-cart" aria-labelledby="rh-cart-title">
    <div class="rh-cart__glow" aria-hidden="true"></div>

    <header class="rh-cart__intro">
        <div class="rh-cart__intro-copy">
            <p class="rh-cart__kicker">خرید امن</p>
            <h1 id="rh-cart-title" class="rh-cart__title">سبد خرید</h1>
            <p class="rh-cart__lede">
                @if ($items->isEmpty())
                    هنوز دوره‌ای انتخاب نکرده‌اید. وقتی آماده بودید، از اینجا ادامه دهید.
                @else
                    {{ fa_digits($itemCount) }} مورد در سبد شماست. قبل از پرداخت، جمع‌بندی را یک‌بار مرور کنید.
                @endif
            </p>
        </div>

        @unless ($items->isEmpty())
            <ol class="rh-cart__steps" aria-label="مراحل خرید">
                <li class="rh-cart__step is-current" aria-current="step">
                    <span class="rh-cart__step-num" aria-hidden="true">۱</span>
                    <span>سبد</span>
                </li>
                <li class="rh-cart__step">
                    <span class="rh-cart__step-num" aria-hidden="true">۲</span>
                    <span>پرداخت</span>
                </li>
                <li class="rh-cart__step">
                    <span class="rh-cart__step-num" aria-hidden="true">۳</span>
                    <span>دسترسی</span>
                </li>
            </ol>
        @endunless
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

    @if ($items->isEmpty())
        <div class="rh-cart__empty">
            <div class="rh-cart__empty-visual" aria-hidden="true">
                <svg viewBox="0 0 120 120" fill="none">
                    <rect x="18" y="28" width="84" height="64" rx="14" stroke="currentColor" stroke-width="3"/>
                    <path d="M38 28V24a22 22 0 0144 0v4" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="46" cy="58" r="5" fill="currentColor"/>
                    <circle cx="74" cy="58" r="5" fill="currentColor"/>
                    <path d="M42 76c6 8 30 8 36 0" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>
            <h2 class="rh-cart__empty-title">سبد خرید خالی است</h2>
            <p class="rh-cart__empty-text">دوره‌ای که می‌خواهید را انتخاب کنید؛ دسترسی بلافاصله بعد از پرداخت فعال می‌شود.</p>
            <a href="{{ route('courses.index') }}" class="rh-cart__cta rh-cart__cta--primary">مشاهده دوره‌ها</a>
        </div>
    @else
        <div class="rh-cart__layout">
            <div class="rh-cart__main">
                <div class="rh-cart__sheet">
                    <div class="rh-cart__spine" aria-hidden="true"><span></span><span></span><span></span></div>

                    <div class="rh-cart__sheet-head">
                        <h2 class="rh-cart__sheet-title">اقلام سبد</h2>
                        <p class="rh-cart__sheet-hint">{{ fa_digits($items->count()) }} محصول</p>
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

                                        <form method="POST" action="{{ route('cart.remove', $item) }}" class="rh-cart__remove-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rh-cart__remove" aria-label="حذف {{ $product->title }} از سبد">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.34.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                حذف
                                            </button>
                                        </form>
                                    </div>

                                    <div class="rh-cart__item-meta">
                                        @if ($product->type === \App\Models\ShopProduct::TYPE_PHYSICAL)
                                            <form method="POST" action="{{ route('cart.update', $item) }}" class="rh-cart__qty" aria-label="تعداد {{ $product->title }}">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" name="quantity" value="{{ max(1, $item->quantity - 1) }}" class="rh-cart__qty-btn" @disabled($item->quantity <= 1) aria-label="کاهش تعداد">−</button>
                                                <span class="rh-cart__qty-value" aria-live="polite">{{ fa_digits($item->quantity) }}</span>
                                                <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}" class="rh-cart__qty-btn" aria-label="افزایش تعداد">+</button>
                                            </form>
                                        @else
                                            <p class="rh-cart__access-note">دسترسی دائمی پس از پرداخت</p>
                                        @endif

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
                        <a href="{{ route('courses.index') }}" class="rh-cart__link-back">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            افزودن دوره دیگر
                        </a>
                    </div>
                </div>
            </div>

            <aside class="rh-cart__aside" aria-labelledby="rh-cart-summary-title">
                <div class="rh-cart__summary">
                    <h2 id="rh-cart-summary-title" class="rh-cart__summary-title">جمع‌بندی پرداخت</h2>

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
                            <dd>{{ fa_digits(number_format($payable)) }} تومان</dd>
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
                            <label for="rh-cart-coupon" class="rh-cart__coupon-label">کد تخفیف دارید؟</label>
                            <div class="rh-cart__coupon-row">
                                <input
                                    id="rh-cart-coupon"
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

                    <a href="{{ route('checkout.index') }}" class="rh-cart__cta rh-cart__cta--primary rh-cart__cta--block">
                        ادامه و پرداخت
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                    </a>

                    @guest
                        <p class="rh-cart__login-hint">
                            برای پرداخت باید
                            <a href="{{ route('login') }}">وارد حساب شوید</a>.
                        </p>
                    @endguest

                    <ul class="rh-cart__trust">
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                            پرداخت امن درگاه بانکی
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                            دسترسی فوری بعد از پرداخت
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                            پشتیبانی از مسیر پنل هنرجو
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    @endif
</section>
@endsection
