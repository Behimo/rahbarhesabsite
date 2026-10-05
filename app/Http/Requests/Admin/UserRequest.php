<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\AccessCatalog;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = trim((string) $this->input('email'));

        $this->merge([
            'email' => $email === '' ? null : $email,
            'last_name' => trim((string) $this->input('last_name')) ?: null,
        ]);

        if ($this->filled('phone')) {
            $this->merge([
                'phone' => PhoneNormalizer::toLocal((string) $this->input('phone')),
            ]);
        }
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : null;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^09\d{9}$/'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'status' => ['required', Rule::in(array_keys(User::statusLabels()))],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', AccessCatalog::guard())],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->route('user');
            $userId = $user instanceof User ? $user->id : null;
            $phone = PhoneNormalizer::toLocal((string) $this->input('phone'));

            $phoneTaken = User::query()
                ->when($userId, fn ($query) => $query->where('id', '!=', $userId))
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
            'phone' => 'شماره موبایل',
            'email' => 'ایمیل',
            'status' => 'وضعیت',
            'role' => 'نقش',
            'password' => 'رمز عبور',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز با رمز عبور یکی نیست.',
        ];
    }

    public function userAttributes(): array
    {
        $user = $this->route('user');
        $validated = $this->safe()->only(['first_name', 'last_name', 'phone', 'email', 'status']);
        $validated['last_name'] = filled($validated['last_name'] ?? null) ? $validated['last_name'] : null;
        $validated['email'] = filled($validated['email'] ?? null) ? $validated['email'] : null;
        $validated['name'] = trim($validated['first_name'].' '.($validated['last_name'] ?? ''));
        $validated['phone'] = PhoneNormalizer::toLocal($validated['phone']);
        $validated['mobile'] = $validated['phone'];

        if ($user instanceof User) {
            if ($validated['phone'] !== ($user->mobile ?: $user->phone)) {
                $validated['mobile_verified_at'] = null;
            }

            if ($validated['email'] !== $user->email) {
                $validated['email_verified_at'] = null;
            }
        }

        if ($this->filled('password')) {
            $validated['password'] = $this->validated('password');
            $validated['is_wp_password'] = false;
        } elseif (! $user instanceof User) {
            $validated['password'] = Str::random(32);
        }

        return $validated;
    }
}
