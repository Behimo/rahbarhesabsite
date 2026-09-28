<?php

namespace App\Http\Requests\Admin;

use App\Models\CmsProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product instanceof CmsProduct ? $product->id : null;

        return [
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('cms_products', 'slug')->ignore($productId)],
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'accent' => ['required', 'in:orange,purple,blue,green'],
            'visual' => ['nullable', 'string', 'max:50'],
            'audience' => ['nullable', 'string', 'max:200'],
            'features' => ['nullable', 'string'],
            'cta' => ['nullable', 'string', 'max:100'],
            'body' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:300'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'dashboard_image' => ['nullable', 'string', 'max:500'],
            'dashboard_image_file' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'نامک',
            'title' => 'عنوان',
            'subtitle' => 'زیرعنوان',
            'description' => 'توضیح کوتاه',
            'accent' => 'رنگ',
            'visual' => 'نمایش',
            'audience' => 'مخاطب',
            'features' => 'ویژگی‌ها',
            'cta' => 'دکمه',
            'body' => 'متن کامل',
            'meta_title' => 'عنوان سئو',
            'meta_description' => 'توضیحات متا',
            'meta_keywords' => 'کلمات کلیدی',
            'og_image' => 'تصویر اشتراک‌گذاری',
            'dashboard_image' => 'آدرس تصویر داشبورد',
            'dashboard_image_file' => 'تصویر داشبورد',
            'sort_order' => 'ترتیب',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.alpha_dash' => 'نامک فقط می‌تواند شامل حروف انگلیسی، عدد، خط تیره و زیرخط باشد.',
            'slug.unique' => 'این نامک قبلاً برای محصول دیگری ثبت شده است.',
            'dashboard_image_file.image' => 'فایل داشبورد باید یک تصویر باشد.',
            'dashboard_image_file.mimes' => 'تصویر داشبورد باید JPG، PNG یا WebP باشد.',
            'dashboard_image_file.max' => 'حجم تصویر داشبورد نباید بیشتر از ۵ مگابایت باشد.',
        ];
    }

    public function productAttributes(): array
    {
        $validated = $this->validated();

        $validated['features'] = array_values(array_filter(
            array_map('trim', explode("\n", (string) ($validated['features'] ?? '')))
        ));
        $validated['is_published'] = $this->boolean('is_published');
        $validated['is_featured'] = $this->boolean('is_featured');

        unset($validated['dashboard_image_file']);

        return $validated;
    }
}
