<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Support\AccessCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $role = $this->route('role');

        $this->merge([
            'label' => trim((string) $this->input('label')),
            'name' => Str::lower(trim((string) $this->input('name'))),
        ]);

        if ($role instanceof Role && $role->is_system) {
            $this->merge(['name' => $role->name]);
        }
    }

    public function rules(): array
    {
        $role = $this->route('role');
        $roleId = $role instanceof Role ? $role->id : null;

        return [
            'label' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'label')->ignore($roleId),
            ],
            'name' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles', 'name')->where('guard_name', AccessCatalog::guard())->ignore($roleId),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(AccessCatalog::labels()))],
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'عنوان نقش',
            'name' => 'شناسه',
            'permissions' => 'دسترسی‌ها',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'شناسه فقط با حروف کوچک انگلیسی، عدد و زیرخط نوشته می‌شود. مثال: support',
            'label.unique' => 'نقشی با این عنوان وجود دارد.',
            'name.unique' => 'این شناسه قبلاً استفاده شده است.',
        ];
    }
}
