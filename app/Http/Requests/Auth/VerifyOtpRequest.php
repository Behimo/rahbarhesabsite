<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        if (is_string($code)) {
            $this->merge([
                'code' => PhoneNormalizer::normalizeOtpCode($code),
            ]);
        }
    }

    public function rules(): array
    {
        $length = max(1, (int) config('otp.length', 6));

        return [
            'code' => ['required', 'string', 'size:'.$length, 'regex:/^\d{'.$length.'}$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'کد تأیید',
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'کد تأیید باید فقط عدد باشد.',
        ];
    }
}
