<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $coupon = $this->route('coupon');
        $couponId = $coupon instanceof Coupon ? $coupon->id : null;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($couponId)],
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:percentage_cart,fixed_cart,percentage_product,fixed_product'],
            'value' => ['required', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit_total' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'is_first_order_only' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'کد تخفیف',
            'title' => 'عنوان',
            'type' => 'نوع',
            'value' => 'مقدار',
            'starts_at' => 'شروع',
            'expires_at' => 'پایان',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'این کد تخفیف قبلاً ثبت شده است.',
        ];
    }

    public function couponAttributes(): array
    {
        $data = $this->validated();
        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $this->boolean('is_active');
        $data['is_first_order_only'] = $this->boolean('is_first_order_only');

        return $data;
    }
}
