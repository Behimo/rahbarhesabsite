<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BuilderSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $builderContent = $this->input('builder_content');

        if (is_string($builderContent)) {
            $decoded = json_decode($builderContent, true);
            $this->merge([
                'builder_content' => is_array($decoded) ? $decoded : null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'builder_content' => ['required', 'array'],
            'builder_content.blocks' => ['present', 'array'],
            'builder_content.blocks.*' => ['array'],
            'builder_content.blocks.*.type' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'builder_content.required' => 'محتوای صفحه‌ساز نامعتبر است.',
            'builder_content.array' => 'محتوای صفحه‌ساز نامعتبر است.',
        ];
    }
}
