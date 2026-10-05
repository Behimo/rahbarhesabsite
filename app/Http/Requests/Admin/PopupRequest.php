<?php

namespace App\Http\Requests\Admin;

use App\Models\CmsPopup;
use App\Services\PopupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:2000'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'button_label' => ['nullable', 'string', 'max:80'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'target_mode' => ['required', Rule::in([CmsPopup::MODE_PAGES, CmsPopup::MODE_RULES])],
            'pages' => ['required_if:target_mode,pages', 'array', 'min:1'],
            'pages.*' => ['string'],
            'rule_match' => ['required_if:target_mode,rules', Rule::in(array_keys(CmsPopup::RULE_MATCHES))],
            'rule_path' => ['nullable', 'string', 'max:255'],
            'audience' => ['required', Rule::in(array_keys(CmsPopup::AUDIENCES))],
            'frequency' => ['required', Rule::in(array_keys(CmsPopup::FREQUENCIES))],
                        'delay_seconds' => ['nullable', 'integer', 'min:0', 'max:300'],
                        'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
                        'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'نام داخلی',
            'title' => 'عنوان',
            'body' => 'متن',
            'image_url' => 'آدرس تصویر',
            'button_label' => 'متن دکمه',
            'button_url' => 'لینک دکمه',
            'starts_at' => 'شروع نمایش',
            'ends_at' => 'پایان نمایش',
            'target_mode' => 'نوع هدف‌گیری',
            'pages' => 'صفحات',
            'rule_match' => 'نوع شرط',
            'rule_path' => 'مسیر',
            'audience' => 'مخاطب',
            'frequency' => 'تکرار نمایش',
            'delay_seconds' => 'تأخیر',
                        'sort_order' => 'اولویت',
                        'priority' => ' اولویت پیشرفته',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateUrls($validator);
            $this->validateTarget($validator);
        });
    }

    public function popupAttributes(): array
    {
        $data = $this->validated();
        $mode = $data['target_mode'];

        return [
            'name' => $data['name'],
            'title' => $data['title'],
            'body' => $data['body'],
            'image_url' => PopupService::safeUrl($data['image_url'] ?? null),
            'button_label' => filled($data['button_label'] ?? null) ? $data['button_label'] : null,
            'button_url' => filled($data['button_label'] ?? null) ? PopupService::safeUrl($data['button_url'] ?? null) : null,
            'is_active' => $this->boolean('is_active'),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'target_mode' => $mode,
            'pages' => $mode === CmsPopup::MODE_PAGES ? array_values(array_unique($data['pages'] ?? [])) : null,
            'rules' => $mode === CmsPopup::MODE_RULES ? [
                'match' => $data['rule_match'],
                'path' => in_array($data['rule_match'], ['contains', 'starts', 'equals'], true)
                    ? trim((string) ($data['rule_path'] ?? ''))
                    : null,
            ] : null,
            'audience' => $data['audience'],
            'frequency' => $data['frequency'],
                        'delay_seconds' => (int) ($data['delay_seconds'] ?? 0),
                        'sort_order' => (int) ($data['sort_order'] ?? 0),
                        'priority' => (int) ($data['priority'] ?? 0),
        ];
    }

    private function validateUrls(Validator $validator): void
    {
        foreach (['image_url' => 'آدرس تصویر', 'button_url' => 'لینک دکمه'] as $field => $label) {
            $value = trim((string) $this->input($field, ''));
            if ($value !== '' && PopupService::safeUrl($value) === null) {
                $validator->errors()->add($field, $label.' باید یک آدرس داخلی مثل /contact یا لینک http/https باشد.');
            }
        }

        if (filled($this->input('button_label')) && ! filled($this->input('button_url'))) {
            $validator->errors()->add('button_url', 'برای دکمه باید لینک هم وارد شود.');
        }
    }

    private function validateTarget(Validator $validator): void
    {
        if ($this->input('target_mode') === CmsPopup::MODE_PAGES) {
            $pages = $this->input('pages', []);
            $service = app(PopupService::class);

            foreach (is_array($pages) ? $pages : [] as $page) {
                if (! is_string($page) || ! $service->isAllowedPageKey($page)) {
                    $validator->errors()->add('pages', 'یکی از صفحات انتخاب‌شده معتبر نیست.');
                    break;
                }
            }

            return;
        }

        $match = (string) $this->input('rule_match');
        if (in_array($match, ['contains', 'starts', 'equals'], true) && trim((string) $this->input('rule_path')) === '') {
            $validator->errors()->add('rule_path', 'مسیر شرط را وارد کنید.');
        }
    }
}
