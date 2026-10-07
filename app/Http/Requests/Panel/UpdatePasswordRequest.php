<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    protected $errorBag = 'password';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'رمز فعلی را وارد کنید.',
            'current_password.current_password' => 'رمز فعلی درست نیست.',
            'password.required' => 'رمز جدید را وارد کنید.',
            'password.min' => 'رمز جدید باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز با رمز جدید یکی نیست.',
        ];
    }
}
