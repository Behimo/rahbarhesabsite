<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CmsImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'export_file' => ['required', 'file', 'mimes:json,txt'],
        ];
    }

    public function attributes(): array
    {
        return [
            'export_file' => 'فایل درون‌ریزی',
        ];
    }
}
