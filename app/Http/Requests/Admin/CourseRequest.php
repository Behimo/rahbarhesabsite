<?php

namespace App\Http\Requests\Admin;

use App\Models\ShopProduct;
use App\Rules\EnglishSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('course');
        $productId = $product instanceof ShopProduct ? $product->id : null;

        return [
            'slug' => ['required', 'string', 'max:100', new EnglishSlug, Rule::unique('shop_products', 'slug')->ignore($productId)],
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'sale_price' => ['nullable', 'integer', 'min:0'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'instructor_id' => ['nullable', 'exists:users,id'],
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'what_you_learn' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'spotplayer_course_id' => ['nullable', 'string', 'max:100'],
            'term_ids' => ['nullable', 'array'],
            'term_ids.*' => ['integer', 'exists:cms_taxonomy_terms,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'نامک',
            'title' => 'عنوان',
            'price' => 'قیمت',
            'level' => 'سطح',
            'instructor_id' => 'مدرس',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'این نامک قبلاً ثبت شده است.',
        ];
    }

    public function payload(): array
    {
        $validated = $this->validated();

        $product = [
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'price' => $validated['price'],
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => $this->boolean('is_published'),
        ];

        foreach (['subtitle', 'description', 'sale_price', 'featured_image', 'meta_title', 'meta_description', 'sort_order'] as $field) {
            if (array_key_exists($field, $validated)) {
                $product[$field] = $validated[$field];
            }
        }

        $course = [
            'instructor_id' => $validated['instructor_id'] ?? null,
            'level' => $validated['level'],
            'what_you_learn' => array_values(array_filter(array_map('trim', explode("\n", $validated['what_you_learn'] ?? '')))),
            'requirements' => array_values(array_filter(array_map('trim', explode("\n", $validated['requirements'] ?? '')))),
        ];

        foreach (['duration_minutes', 'spotplayer_course_id'] as $field) {
            if (array_key_exists($field, $validated)) {
                $course[$field] = $validated[$field];
            }
        }

        return ['product' => $product, 'course' => $course];
    }
}
