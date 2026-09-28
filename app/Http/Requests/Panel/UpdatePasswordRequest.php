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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'رمز جدید را وارد کنید.',
            'password.min' => 'رمز جدید باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز با رمز جدید یکی نیست.',
        ];
    }
}
