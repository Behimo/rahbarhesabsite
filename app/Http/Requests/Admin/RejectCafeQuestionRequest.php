<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectCafeQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rejection_reason' => 'علت رد',
        ];
    }
}
