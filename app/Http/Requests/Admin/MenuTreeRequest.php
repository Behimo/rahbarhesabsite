<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MenuTreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tree' => ['required', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tree' => 'ساختار منو',
        ];
    }
}
