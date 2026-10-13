<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AnswerCafeQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('body')) {
            $this->merge([
                'body' => clean_html($this->input('body')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'body' => [
                'required',
                'string',
                'max:50000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $plain = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $plain = preg_replace('/\x{00A0}/u', ' ', $plain) ?? $plain;
                    $plain = trim(preg_replace('/\s+/u', ' ', $plain) ?? $plain);

                    if (mb_strlen($plain) < 10) {
                        $fail('پاسخ باید حداقل ۱۰ کاراکتر باشد.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'پاسخ',
        ];
    }
}
