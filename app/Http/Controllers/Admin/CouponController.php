<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = Coupon::query()->latest()->paginate(20);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        return view('admin.coupons.form', ['coupon' => new Coupon([
            'type' => 'percentage_cart',
            'is_active' => true,
            'usage_limit_per_user' => 1,
        ])]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        Coupon::query()->create($request->couponAttributes());

        return redirect()->route('admin.coupons.index')->with('success', 'کد تخفیف ایجاد شد.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.form', compact('coupon'));
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($request->couponAttributes());

        return redirect()->route('admin.coupons.index')->with('success', 'کد تخفیف به‌روزرسانی شد.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'کد تخفیف حذف شد.');
    }
}
