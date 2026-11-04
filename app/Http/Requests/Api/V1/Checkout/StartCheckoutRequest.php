<?php

namespace App\Http\Requests\Api\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class StartCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'gateway' => ['nullable', 'string', 'max:32'],
            'idempotency_key' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,80}$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'محصول',
            'coupon_code' => 'کد تخفیف',
            'gateway' => 'درگاه پرداخت',
            'idempotency_key' => 'کلید تکرار',
        ];
    }

    public function messages(): array
    {
        return [
            'idempotency_key.required' => 'هدر Idempotency-Key الزامی است.',
            'idempotency_key.regex' => 'هدر Idempotency-Key باید ۸ تا ۸۰ کاراکتر از حروف، عدد، خط تیره یا زیرخط باشد.',
        ];
    }
}
