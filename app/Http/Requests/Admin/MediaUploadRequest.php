<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
            'alt' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'فایل',
            'alt' => 'متن جایگزین',
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'فرمت فایل مجاز نیست.',
            'file.max' => 'حجم فایل نباید بیشتر از ۵ مگابایت باشد.',
        ];
    }
}
