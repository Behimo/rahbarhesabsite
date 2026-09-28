<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RedirectImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rows' => 'ردیف‌های ریدایرکت',
        ];
    }
}
