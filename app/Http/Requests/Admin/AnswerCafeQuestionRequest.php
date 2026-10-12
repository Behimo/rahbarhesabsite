<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AnswerCafeQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
