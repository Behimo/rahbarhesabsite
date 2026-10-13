<?php

namespace App\Http\Requests\Admin;

use App\Models\CafeQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCafeQuestionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(CafeQuestion::statusLabels()))],
            'rejection_reason' => [
                'nullable',
                'required_if:status,'.CafeQuestion::STATUS_REJECTED,
                'string',
                'min:5',
                'max:2000',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'وضعیت',
            'rejection_reason' => 'علت رد',
        ];
    }
}
