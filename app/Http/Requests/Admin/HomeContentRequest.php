<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HomeContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'heading_small' => ['nullable', 'string', 'max:200'],
            'heading' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:1000'],
            'banner_image' => ['nullable', 'string', 'max:500'],
            'courses_title' => ['nullable', 'string', 'max:200'],
            'free_courses_title' => ['nullable', 'string', 'max:200'],
            'news_title' => ['nullable', 'string', 'max:200'],
            'app_download_url' => ['nullable', 'string', 'max:500'],
            'faqs' => ['nullable', 'array', 'max:12'],
            'faqs.*.q' => ['nullable', 'string', 'max:300'],
            'faqs.*.a' => ['nullable', 'string', 'max:1000'],
            'faqs.*.href' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function content(): array
    {
        $validated = $this->validated();
        $faqs = array_values(array_filter($validated['faqs'] ?? [], fn ($faq) => ! empty($faq['q'])));

        return [
            'about' => [
                'heading_small' => $validated['heading_small'] ?? '',
                'heading' => $validated['heading'] ?? '',
                'description' => $validated['description'] ?? '',
                'banner_image' => $validated['banner_image'] ?? '',
            ],
            'sections' => [
                'courses_title' => $validated['courses_title'] ?? '',
                'free_courses_title' => $validated['free_courses_title'] ?? '',
                'news_title' => $validated['news_title'] ?? '',
            ],
            'app_download_url' => $validated['app_download_url'] ?? '',
            'faqs' => $faqs,
        ];
    }
}
