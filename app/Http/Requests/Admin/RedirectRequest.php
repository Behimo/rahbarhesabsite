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
        $fromQuery = (string) $this->input('from_query', '');

        return [
            'from_path' => [
                'required',
                'string',
                'max:500',
                Rule::unique('cms_redirects', 'from_path')
                    ->where(function ($q) use ($fromQuery) {
                        // Empty query matches both '' and legacy NULL rows, since
                        // MySQL treats NULL as distinct in the unique index.
                        if ($fromQuery === '') {
                            $q->where(function ($q) {
                                $q->where('from_query', '')->orWhereNull('from_query');
                            });
                        } else {
                            $q->where('from_query', $fromQuery);
                        }
                    })
                    ->ignore($redirectId),
            ],
            'from_query' => ['nullable', 'string', 'max:255'],
            'match_query' => ['sometimes', 'boolean'],
            // Either a site-relative path (/foo) or an external http(s) URL.
            'to_path' => ['required', 'string', 'max:500', 'regex:/^(?:https?:\/\/[^\s\\\\]+|\/(?!\/)[^\s\\\\]*)$/i'],
            'status_code' => ['required', 'in:301,302,410'],
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
        $data['from_query'] = (string) ($data['from_query'] ?? '');
        $data['match_query'] = $this->boolean('match_query', $data['from_query'] !== '');
        $data['is_active'] = $this->boolean('is_active', true);

        return $data;
    }
}
