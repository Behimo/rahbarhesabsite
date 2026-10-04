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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^09\d{9}$/', Rule::unique('users', 'phone')->ignore($userId)],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['required', Rule::in(array_keys(AccessCatalog::roles()))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(AccessCatalog::labels()))],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'phone' => 'شماره موبایل',
            'email' => 'ایمیل',
            'role' => 'نقش',
            'permissions' => 'دسترسی‌ها',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'شماره موبایل معتبر نیست.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
        ];
    }

    public function userAttributes(): array
    {
        $user = $this->route('user');
        $validated = $this->safe()->only(['name', 'phone', 'email']);
        $validated['phone'] = PhoneNormalizer::toLocal($validated['phone']);
        $validated['mobile'] = $validated['phone'];

        if (! $user instanceof User) {
            $validated['password'] = Str::random(32);
        }

        return $validated;
    }
}
