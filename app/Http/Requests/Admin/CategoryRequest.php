<?php

namespace App\Http\Requests\Admin;

use App\Models\CmsCategory;
use App\Rules\EnglishSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = $category instanceof CmsCategory ? $category->id : null;

        return [
            'slug' => ['required', 'string', 'max:80', new EnglishSlug, Rule::unique('cms_categories', 'slug')->ignore($categoryId)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'نامک',
            'name' => 'نام',
            'description' => 'توضیحات',
            'sort_order' => 'ترتیب',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'این نامک قبلاً برای دسته‌بندی دیگری ثبت شده است.',
        ];
    }
}
