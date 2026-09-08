<?php

namespace App\Http\Controllers\Site;

use App\Models\CartItem;
use App\Models\ShopProduct;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\SeoService;
use App\Services\SiteDataService;
use App\Services\ZarinpalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends SiteController
{
    public function __construct(
        SiteDataService $siteData,
        SeoService $seo,
        private CartService $cart,
        private OrderService $orders,
        private ZarinpalService $zarinpal,
    ) {
        parent::__construct($siteData, $seo);
    }

    public function cart(): View
    {
        $items = $this->cart->items();
        $subtotal = $this->cart->subtotal();

        $seo = $this->seo->meta(['title' => 'سبد خرید']);

        return $this->render('pages.shop.cart', compact('items', 'subtotal', 'seo'));
    }

    public function addToCart(ShopProduct $product): RedirectResponse
    {
        if (! $product->is_published) {
            return back()->with('error', 'محصول در دسترس نیست.');
        }

        if ($product->isCourse() && auth()->check() && auth()->user()->isEnrolledIn($product->course)) {
            return back()->with('error', 'شما قبلاً در این دوره ثبت‌نام کرده‌اید.');
        }

        $this->cart->add($product);

        return redirect()->route('cart.index')->with('success', 'به سبد خرید اضافه شد.');
    }

    public function updateCart(Request $request, CartItem $item): RedirectResponse
    {
        $this->authorizeCartItem($item);
        $this->cart->update($item, (int) $request->input('quantity', 1));

        return back()->with('success', 'سبد خرید به‌روزرسانی شد.');
    }

    public function removeFromCart(CartItem $item): RedirectResponse
    {
        $this->authorizeCartItem($item);
        $this->cart->remove($item);

        return back()->with('success', 'از سبد خرید حذف شد.');
    }

    public function checkout(): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'برای پرداخت وارد حساب کاربری شوید.');
        }

        if ($this->cart->items()->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'سبد خرید خالی است.');
        }

        $items = $this->cart->items();
        $subtotal = $this->cart->subtotal();

        $seo = $this->seo->meta(['title' => 'تسویه حساب']);

        return $this->render('pages.shop.checkout', compact('items', 'subtotal', 'seo'));
    }

    public function processCheckout(Request $request): RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $order = $this->orders->createFromCart(auth()->user());
        $this->cart->clear();

        if ($order->total <= 0) {
            $order->update(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
            $this->orders->fulfill($order);

            return redirect()->route('checkout.success', $order);
        }

        try {
            $paymentUrl = $this->zarinpal->requestPayment($order);

            return redirect()->away($paymentUrl);
        } catch (\Throwable $e) {
            return redirect()->route('checkout.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        $authority = $request->query('Authority');
        $status = $request->query('Status');

        if ($status !== 'OK' || ! $authority) {
            return redirect()->route('cart.index')->with('error', 'پرداخت لغو شد.');
        }

        $payment = \App\Models\Payment::query()->where('authority', $authority)->first();

        if (! $payment) {
            return redirect()->route('cart.index')->with('error', 'تراکنش یافت نشد.');
        }

        $verified = $this->zarinpal->verify($authority, $payment->amount);

        if ($verified) {
            return redirect()->route('checkout.success', $payment->order);
        }

        return redirect()->route('cart.index')->with('error', 'پرداخت ناموفق بود.');
    }

    public function success(\App\Models\Order $order): View|RedirectResponse
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        return $this->render('pages.shop.success', compact('order'));
    }

    private function authorizeCartItem(CartItem $item): void
    {
        if (auth()->check() && $item->user_id === auth()->id()) {
            return;
        }

        if ($item->session_id === session()->getId()) {
            return;
        }

        abort(403);
    }
}
