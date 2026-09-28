<?php

namespace App\Http\Requests\Admin;

use App\Rules\EnglishSlug;
use Illuminate\Foundation\Http\FormRequest;

class TaxonomyTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:100', new EnglishSlug],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:cms_taxonomy_terms,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'نامک',
            'name' => 'نام',
            'parent_id' => 'والد',
            'sort_order' => 'ترتیب',
        ];
    }
}
