<?php

namespace App\Http\Requests\Admin;

use App\Models\CmsRedirect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $redirect = $this->route('redirect');
        $redirectId = $redirect instanceof CmsRedirect ? $redirect->id : null;

        return [
            'from_path' => ['required', 'string', 'max:255', Rule::unique('cms_redirects', 'from_path')->ignore($redirectId)],
            'to_path' => ['required', 'string', 'max:255', 'regex:/^\/(?!\/)[^\s\\\\]*$/'],
            'status_code' => ['required', 'in:301,302'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'from_path' => 'مسیر مبدأ',
            'to_path' => 'مسیر مقصد',
            'status_code' => 'کد وضعیت',
        ];
    }

    public function messages(): array
    {
        return [
            'from_path.unique' => 'برای این مسیر قبلاً ریدایرکت ثبت شده است.',
        ];
    }

    public function redirectAttributes(): array
    {
        $data = $this->validated();
        $data['from_path'] = '/'.ltrim($data['from_path'], '/');
        $data['is_active'] = $this->boolean('is_active', true);

        return $data;
    }
}
