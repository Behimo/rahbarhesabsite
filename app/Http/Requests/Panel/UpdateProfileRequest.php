<?php

namespace App\Http\Requests\Panel;

use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => PhoneNormalizer::toLocal((string) $this->input('phone')),
            ]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['required', 'string', 'max:20', 'regex:/^09\d{9}$/'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            $phone = PhoneNormalizer::toLocal((string) $this->input('phone'));

            $phoneTaken = User::query()
                ->where('id', '!=', $user?->id)
                ->where(function ($query) use ($phone) {
                    $query->where('phone', $phone)->orWhere('mobile', $phone);
                })
                ->exists();

            if ($phoneTaken) {
                $validator->errors()->add('phone', 'این شماره موبایل قبلاً ثبت شده است.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'email' => 'ایمیل',
            'phone' => 'شماره موبایل',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'نام را وارد کنید.',
            'email.email' => 'ایمیل را درست وارد کنید.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'phone.required' => 'شماره موبایل را وارد کنید.',
            'phone.regex' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
        ];
    }
}
