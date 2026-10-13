<?php

namespace App\Http\Requests\Admin;

use App\Models\CafeQuestion;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCafeQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('body')) {
            $this->merge([
                'body' => clean_html($this->input('body')),
            ]);
        }
    }

    public function rules(): array
    {
        $hasOfficial = $this->route('question')?->officialAnswer()->exists();

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('type', Category::TYPE_CAFE)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'status' => ['required', 'string', Rule::in(array_keys(CafeQuestion::statusLabels()))],
            'rejection_reason' => [
                'nullable',
                'required_if:status,'.CafeQuestion::STATUS_REJECTED,
                'string',
                'min:5',
                'max:2000',
            ],
            'body' => [
                Rule::requiredIf((bool) $hasOfficial),
                'nullable',
                'string',
                'max:50000',
                function (string $attribute, mixed $value, \Closure $fail) use ($hasOfficial): void {
                    if (! $hasOfficial || $value === null || $value === '') {
                        return;
                    }

                    $plain = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $plain = preg_replace('/\x{00A0}/u', ' ', $plain) ?? $plain;
                    $plain = trim(preg_replace('/\s+/u', ' ', $plain) ?? $plain);

                    if (mb_strlen($plain) < 10) {
                        $fail('پاسخ باید حداقل ۱۰ کاراکتر باشد.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'دسته سوال',
            'status' => 'وضعیت',
            'rejection_reason' => 'علت رد',
            'body' => 'پاسخ',
        ];
    }
}
