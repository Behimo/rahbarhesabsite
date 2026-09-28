<?php

namespace App\Http\Requests\Admin;

use App\Rules\EnglishSlug;
use Illuminate\Foundation\Http\FormRequest;

class TaxonomyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:100', new EnglishSlug, 'unique:cms_taxonomies,slug'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:category,tag'],
            'object_types' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'نامک',
            'name' => 'نام',
            'type' => 'نوع',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'این نامک قبلاً ثبت شده است.',
        ];
    }
}
