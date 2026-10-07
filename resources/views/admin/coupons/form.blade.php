@extends('layouts.admin')

@php
    $type = old('type', $coupon->type ?: 'percentage_cart');
    $types = [
        'percentage_cart' => 'درصد از سبد',
        'fixed_cart' => 'مبلغ ثابت سبد',
        'percentage_product' => 'درصد محصول',
        'fixed_product' => 'مبلغ ثابت محصول',
    ];
@endphp

@section('title', $coupon->exists ? 'ویرایش کد تخفیف' : 'کد تخفیف جدید')

@section('heading', $coupon->exists ? 'ویرایش: '.$coupon->code : 'کد تخفیف جدید')

@section('lede', 'مقدار تخفیف و سقف مصرف را مشخص کنید. فعال بودن و بازه زمانی کنار فرم می‌ماند.')

@section('vendor-style')
@include('admin.partials.composer-styles')
@endsection

@section('content')
<form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}">
    @csrf
    @if ($coupon->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">تخفیف</h5></div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="coupon-code">کد *</label>
                            <input id="coupon-code" type="text" name="code" value="{{ old('code', $coupon->code) }}" class="form-control" dir="ltr" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="coupon-title">عنوان</label>
                            <input id="coupon-title" type="text" name="title" value="{{ old('title', $coupon->title) }}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="form-label">نوع *</div>
                        <div class="sb-pills sb-pills--2">
                            @foreach ($types as $value => $label)
                                <label class="sb-pill">
                                    <input type="radio" name="type" value="{{ $value }}" @checked($type === $value)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="coupon-value">مقدار *</label>
                            <input id="coupon-value" type="number" step="0.01" name="value" value="{{ old('value', $coupon->value) }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="coupon-max">سقف تخفیف (تومان)</label>
                            <input id="coupon-max" type="number" name="max_discount_amount" value="{{ old('max_discount_amount', $coupon->max_discount_amount) }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="coupon-min">حداقل سبد (تومان)</label>
                            <input id="coupon-min" type="number" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="coupon-limit-total">سقف مصرف کل</label>
                            <input id="coupon-limit-total" type="number" name="usage_limit_total" value="{{ old('usage_limit_total', $coupon->usage_limit_total) }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="coupon-limit-user">سقف هر کاربر</label>
                            <input id="coupon-limit-user" type="number" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user ?? 1) }}" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 admin-form-side">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">اعتبار</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', $coupon->is_active))>
                        <label class="form-check-label" for="is_active">فعال</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_first_order_only" value="1" class="form-check-input" id="first_only" @checked(old('is_first_order_only', $coupon->is_first_order_only))>
                        <label class="form-check-label" for="first_only">فقط اولین خرید</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="coupon-start">شروع</label>
                        <input id="coupon-start" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($coupon->starts_at)->format('Y-m-d\TH:i')) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="coupon-end">انقضا</label>
                        <input id="coupon-end" type="datetime-local" name="expires_at" value="{{ old('expires_at', optional($coupon->expires_at)->format('Y-m-d\TH:i')) }}" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">ذخیره</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
