<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'عنوان سوال',
            'body' => 'متن سوال',
        ];
    }
}
