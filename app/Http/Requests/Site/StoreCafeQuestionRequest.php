<?php

namespace App\Http\Requests\Site;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCafeQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:180'],
            'body' => ['required', 'string', 'min:20', 'max:10000'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('type', Category::TYPE_CAFE)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'عنوان سوال',
            'body' => 'متن سوال',
            'category_id' => 'دسته سوال',
        ];
    }
}
