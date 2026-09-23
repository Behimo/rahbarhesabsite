<?php

namespace App\Http\Controllers\Site;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\SpotplayerLicense;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Services\PaymentGatewayManager;
use App\Services\SeoService;
use App\Services\SiteDataService;
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
        private PaymentGatewayManager $payments,
        private CouponService $coupons,
    ) {
        parent::__construct($siteData, $seo);
    }

    public function cart(): View
    {
        return $this->render('pages.shop.cart', $this->cartViewData() + [
            'seo' => $this->seo->meta(['title' => 'سبد خرید']),
        ]);
    }

    public function addToCart(ShopProduct $product): RedirectResponse
    {
        if (! $product->is_published) {
            return back()->with('error', 'محصول در دسترس نیست.');
        }

        if (auth()->check()) {
            foreach ($product->relatedCourses() as $course) {
                if (auth()->user()->isEnrolledIn($course)) {
                    return back()->with('error', 'شما قبلاً در این دوره ثبت‌نام کرده‌اید.');
                }
            }
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

    public function applyCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        try {
            $this->coupons->apply(
                $validated['code'],
                $this->cart->items(),
                $this->cart->subtotal(),
                auth()->user()
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'کد تخفیف اعمال شد.');
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->coupons->forget();

        return back()->with('success', 'کد تخفیف حذف شد.');
    }

    public function checkout(): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'برای پرداخت وارد حساب کاربری شوید.');
        }

        if ($this->cart->items()->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'سبد خرید خالی است.');
        }

        return $this->render('pages.shop.checkout', $this->cartViewData() + [
            'seo' => $this->seo->meta(['title' => 'تسویه حساب']),
        ]);
    }

    public function processCheckout(Request $request): RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        try {
            $order = $this->orders->createFromCart(
                auth()->user(),
                $request->ip(),
                (string) $request->userAgent()
            );
        } catch (\RuntimeException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        if ($order->total <= 0) {
            $this->orders->markPaid($order);

            return redirect()->route('checkout.success', $order);
        }

        $gateway = $request->input('gateway', config('cms.payment_gateway', 'zibal'));

        try {
            $paymentUrl = $this->payments->requestPayment($order, $gateway);

            return redirect()->away($paymentUrl);
        } catch (\Throwable $e) {
            return redirect()->route('checkout.index')->with('error', $e->getMessage());
        }
    }

    public function retryPayment(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);

        if (! $order->canRetryPayment()) {
            return redirect()->route('panel.orders')->with('error', 'این سفارش قابل پرداخت مجدد نیست.');
        }

        $gateway = $request->input('gateway', config('cms.payment_gateway', 'zibal'));

        try {
            $order->update(['status' => Order::STATUS_PENDING]);
            $paymentUrl = $this->payments->requestPayment($order, $gateway);

            return redirect()->away($paymentUrl);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        $gateway = $request->query('gateway', config('cms.payment_gateway', 'zibal'));

        $payment = $this->payments->verifyCallback($gateway, $request->query());

        if ($payment) {
            return redirect()->route('checkout.success', $payment->order);
        }

        $order = $this->findOrderFromCallback($gateway, $request->query());

        return redirect()
            ->route('checkout.failed', array_filter(['order' => $order]))
            ->with('error', 'پرداخت ناموفق یا لغو شد. سبد خرید شما حفظ شده است.');
    }

    public function success(Order $order): View|RedirectResponse
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['items.product.course', 'payment']);

        $licenses = SpotplayerLicense::query()
            ->with('course.product')
            ->where('user_id', $order->user_id)
            ->where('order_id', $order->id)
            ->get();

        return $this->render('pages.shop.success', [
            'order' => $order,
            'licenses' => $licenses,
            'seo' => $this->seo->meta(['title' => 'پرداخت موفق']),
        ]);
    }

    public function failed(?Order $order = null): View
    {
        if ($order && auth()->check() && $order->user_id !== auth()->id()) {
            abort(403);
        }

        if ($order && ! auth()->check()) {
            $order = null;
        }

        if ($order) {
            $order->load(['payment']);
        }

        return $this->render('pages.shop.failed', [
            'order' => $order,
            'message' => session('error') ?? 'پرداخت ناموفق یا لغو شد. سبد خرید شما حفظ شده است.',
            'seo' => $this->seo->meta(['title' => 'پرداخت ناموفق']),
        ]);
    }

    /** @return array<string, mixed> */
    private function cartViewData(): array
    {
        $items = $this->cart->items();
        $subtotal = $this->cart->subtotal();
        $coupon = $this->coupons->applied(auth()->user());
        $discount = $coupon ? $this->coupons->discountFor($coupon, $items) : 0;
        $total = max(0, $subtotal - $discount);

        return compact('items', 'subtotal', 'coupon', 'discount', 'total');
    }

    /** @param  array<string, mixed>  $query */
    private function findOrderFromCallback(string $gateway, array $query): ?Order
    {
        $authority = $query['trackId'] ?? $query['Authority'] ?? $query['authority'] ?? null;

        if (! $authority) {
            return null;
        }

        return Payment::query()
            ->where('gateway', $gateway)
            ->where('authority', (string) $authority)
            ->first()
            ?->order;
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
