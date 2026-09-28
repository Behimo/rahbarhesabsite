<?php

namespace App\Http\Requests\Admin;

use App\Models\CmsMenu;
use App\Rules\EnglishSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $menu = $this->route('menu');
        $menuId = $menu instanceof CmsMenu ? $menu->id : null;

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', new EnglishSlug, Rule::unique('cms_menus', 'slug')->ignore($menuId)],
            'location' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'slug' => 'نامک',
            'location' => 'محل',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'این نامک قبلاً برای منوی دیگری ثبت شده است.',
        ];
    }
}
