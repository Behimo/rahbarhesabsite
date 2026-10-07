<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\CmsSidebar;
use App\Services\SidebarService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SidebarRequest extends FormRequest
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
            'is_active' => ['sometimes', 'boolean'],
            'target_mode' => ['required', Rule::in([CmsSidebar::MODE_PAGES, CmsSidebar::MODE_RULES])],
            'pages' => ['required_if:target_mode,pages', 'array', 'min:1'],
            'pages.*' => ['string'],
            'rule_match' => ['required_if:target_mode,rules', Rule::in(array_keys(CmsSidebar::RULE_MATCHES))],
            'rule_path' => ['nullable', 'string', 'max:255'],
            'source' => ['required', Rule::in(array_keys(CmsSidebar::SOURCES))],
            'selection' => ['required', Rule::in(array_keys(CmsSidebar::SELECTIONS))],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'limit' => ['required', 'integer', 'min:1', 'max:12'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'نام داخلی',
            'title' => 'عنوان',
            'target_mode' => 'نوع هدف‌گیری',
            'pages' => 'صفحات',
            'rule_match' => 'نوع شرط',
            'rule_path' => 'مسیر',
            'source' => 'نوع محتوا',
            'selection' => 'نحوه انتخاب',
            'category_id' => 'دسته',
            'limit' => 'تعداد',
            'sort_order' => 'ترتیب',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateTarget($validator);
            $this->validateCategory($validator);
        });
    }

    public function sidebarAttributes(): array
    {
        $data = $this->validated();
        $mode = $data['target_mode'];
        $selection = $data['selection'];

        return [
            'name' => $data['name'],
            'title' => $data['title'],
            'is_active' => $this->boolean('is_active'),
            'target_mode' => $mode,
            'pages' => $mode === CmsSidebar::MODE_PAGES ? array_values(array_unique($data['pages'] ?? [])) : null,
            'rules' => $mode === CmsSidebar::MODE_RULES ? [
                'match' => $data['rule_match'],
                'path' => in_array($data['rule_match'], ['contains', 'starts', 'equals'], true)
                    ? trim((string) ($data['rule_path'] ?? ''))
                    : null,
            ] : null,
            'source' => $data['source'],
            'selection' => $selection,
            'category_id' => $selection === CmsSidebar::SELECTION_CATEGORY ? (int) $data['category_id'] : null,
            'limit' => (int) $data['limit'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validateTarget(Validator $validator): void
    {
        if ($this->input('target_mode') === CmsSidebar::MODE_PAGES) {
            $pages = $this->input('pages', []);
            $service = app(SidebarService::class);

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

    private function validateCategory(Validator $validator): void
    {
        if ($this->input('selection') !== CmsSidebar::SELECTION_CATEGORY) {
            return;
        }

        $categoryId = $this->input('category_id');
        if (! filled($categoryId)) {
            $validator->errors()->add('category_id', 'برای این نحوه انتخاب باید یک دسته مشخص کنید.');

            return;
        }

        $expected = $this->input('source') === CmsSidebar::SOURCE_POST
            ? Category::TYPE_POST
            : Category::TYPE_PRODUCT;

        $matches = Category::query()
            ->whereKey($categoryId)
            ->where('type', $expected)
            ->exists();

        if (! $matches) {
            $validator->errors()->add('category_id', 'این دسته با نوع محتوای انتخاب‌شده هم‌خوان نیست.');
        }
    }
}
