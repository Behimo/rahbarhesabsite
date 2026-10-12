<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class StoreCafeAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:10', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'پاسخ',
        ];
    }
}
