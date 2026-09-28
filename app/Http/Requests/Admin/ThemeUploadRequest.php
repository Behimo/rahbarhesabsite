<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ThemeUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme_zip' => ['required', 'file', 'mimes:zip', 'max:51200'],
        ];
    }

    public function attributes(): array
    {
        return [
            'theme_zip' => 'فایل قالب',
        ];
    }
}
