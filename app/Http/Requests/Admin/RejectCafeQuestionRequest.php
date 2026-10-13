<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectCafeQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('type', Category::TYPE_CAFE)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'rejection_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'دسته سوال',
            'rejection_reason' => 'علت رد',
        ];
    }
}
