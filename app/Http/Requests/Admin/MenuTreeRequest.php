<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MenuTreeRequest extends FormRequest
{
    public const MAX_DEPTH = 20;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tree' => ['present', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tree' => 'ساختار منو',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $tree = $this->input('tree');

            if (! is_array($tree)) {
                return;
            }

            $this->validateLevel($tree, $validator, 'tree', 1);
        });
    }

    private function validateLevel(array $nodes, Validator $validator, string $path, int $depth): void
    {
        if ($depth > self::MAX_DEPTH) {
            $validator->errors()->add($path, 'عمق منو نمی‌تواند بیشتر از '.self::MAX_DEPTH.' سطح باشد.');

            return;
        }

        foreach ($nodes as $index => $node) {
            $key = $path.'.'.$index;

            if (! is_array($node)) {
                $validator->errors()->add($key, 'آیتم منو نامعتبر است.');

                continue;
            }

            $label = trim((string) ($node['label'] ?? ''));

            if ($label === '') {
                $validator->errors()->add($key.'.label', 'برچسب آیتم الزامی است.');
            } elseif (mb_strlen($label) > 255) {
                $validator->errors()->add($key.'.label', 'برچسب آیتم طولانی است.');
            }

            $type = $node['type'] ?? 'custom';

            if (! in_array($type, ['custom', 'route', 'page', 'post', 'course'], true)) {
                $validator->errors()->add($key.'.type', 'نوع آیتم نامعتبر است.');
            }

            if ($type === 'custom' && trim((string) ($node['url'] ?? '')) === '') {
                $validator->errors()->add($key.'.url', 'آدرس لینک الزامی است.');
            }

            if ($type === 'route' && trim((string) ($node['route_name'] ?? '')) === '') {
                $validator->errors()->add($key.'.route_name', 'نام مسیر الزامی است.');
            }

            if (in_array($type, ['page', 'post', 'course'], true) && trim((string) ($node['meta']['slug'] ?? '')) === '') {
                $validator->errors()->add($key.'.meta.slug', 'انتخاب مقصد الزامی است.');
            }

            if (! isset($node['children'])) {
                continue;
            }

            if (! is_array($node['children'])) {
                $validator->errors()->add($key.'.children', 'زیرمنو نامعتبر است.');

                continue;
            }

            $this->validateLevel($node['children'], $validator, $key.'.children', $depth + 1);
        }
    }
}
