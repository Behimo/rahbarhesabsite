@extends('layouts.admin')

@section('title', $coupon->exists ? 'ویرایش کد تخفیف' : 'کد تخفیف جدید')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.coupons.index') }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت</a>
    <h4 class="mt-2 mb-0">{{ $coupon->exists ? 'ویرایش: '.$coupon->code : 'کد تخفیف جدید' }}</h4>
</div>

<form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="card" style="max-width: 44rem;">
    @csrf
    @if ($coupon->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">کد *</label>
                <input type="text" name="code" value="{{ old('code', $coupon->code) }}" class="form-control" dir="ltr" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">عنوان</label>
                <input type="text" name="title" value="{{ old('title', $coupon->title) }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">نوع *</label>
                <select name="type" class="form-select">
                    <option value="percentage_cart" @selected(old('type', $coupon->type) === 'percentage_cart')>درصد از سبد</option>
                    <option value="fixed_cart" @selected(old('type', $coupon->type) === 'fixed_cart')>مبلغ ثابت سبد</option>
                    <option value="percentage_product" @selected(old('type', $coupon->type) === 'percentage_product')>درصد محصول</option>
                    <option value="fixed_product" @selected(old('type', $coupon->type) === 'fixed_product')>مبلغ ثابت محصول</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">مقدار *</label>
                <input type="number" step="0.01" name="value" value="{{ old('value', $coupon->value) }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">سقف تخفیف (تومان)</label>
                <input type="number" name="max_discount_amount" value="{{ old('max_discount_amount', $coupon->max_discount_amount) }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">حداقل سبد (تومان)</label>
                <input type="number" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">سقف مصرف کل</label>
                <input type="number" name="usage_limit_total" value="{{ old('usage_limit_total', $coupon->usage_limit_total) }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">سقف هر کاربر</label>
                <input type="number" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user ?? 1) }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">شروع</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($coupon->starts_at)->format('Y-m-d\TH:i')) }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">انقضا</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', optional($coupon->expires_at)->format('Y-m-d\TH:i')) }}" class="form-control">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', $coupon->is_active))>
                    <label class="form-check-label" for="is_active">فعال</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="is_first_order_only" value="1" class="form-check-input" id="first_only" @checked(old('is_first_order_only', $coupon->is_first_order_only))>
                    <label class="form-check-label" for="first_only">فقط اولین خرید</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-4">ذخیره</button>
    </div>
</form>
@endsection
