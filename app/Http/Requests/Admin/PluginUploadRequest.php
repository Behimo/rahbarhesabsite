<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PluginUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plugin_zip' => ['required', 'file', 'mimes:zip', 'max:51200'],
        ];
    }

    public function attributes(): array
    {
        return [
            'plugin_zip' => 'فایل افزونه',
        ];
    }
}
