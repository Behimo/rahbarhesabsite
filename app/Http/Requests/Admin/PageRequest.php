<?php

namespace App\Http\Requests\Admin;

use App\Models\CmsPage;
use App\Rules\EnglishSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    /**
     * @deprecated Kept for backwards compatibility; the canonical list lives in
     *             config/seo.php so routing and validation can never diverge.
     */
    public const RESERVED_SLUGS = [
        'admin', 'blog', 'products', 'courses', 'cart', 'checkout', 'login', 'register', 'panel',
        'contact', 'services', 'about', 'why-bisan', 'sitemap.xml', 'robots.txt', 'api',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $page = $this->page();
        $isSystem = (bool) $page?->is_system;
        $reservedSlugs = array_values(array_unique(array_merge(
            config('seo.reserved_prefixes', []),
            self::RESERVED_SLUGS,
        )));

        return [
            'slug' => $isSystem
                ? ['sometimes', 'nullable']
                : ['required', 'string', 'max:100', new EnglishSlug, Rule::unique('cms_pages', 'slug')->ignore($page?->id), Rule::notIn($reservedSlugs)],
            'title' => ['required', 'string', 'max:200'],
            'template' => ['nullable', 'in:system,content,wp-html'],
            'status' => ['nullable', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:300'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'robots' => ['required', 'string', 'max:50'],
            'body_html' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'نامک',
            'title' => 'عنوان',
            'robots' => 'ربات‌ها',
            'body_html' => 'متن صفحه',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.not_in' => 'این آدرس رزرو شده است.',
            'slug.unique' => 'این نامک قبلاً برای صفحه دیگری ثبت شده است.',
        ];
    }

    public function pageAttributes(): array
    {
        $page = $this->page();
        $validated = $this->validated();

        $content = $page?->content ?? [];
        if ($this->filled('body_html')) {
            $content['body_html'] = $validated['body_html'];
        }
        unset($validated['body_html']);

        $validated['content'] = $content;
        $validated['is_published'] = $this->boolean('is_published');
        $validated['show_in_nav'] = $this->boolean('show_in_nav');
        $validated['is_system'] = $page?->is_system ?? false;
        $validated['status'] = $validated['status'] ?? ($validated['is_published'] ? 'published' : 'draft');

        if (! $page) {
            $validated['template'] = 'content';
        } elseif ($page->is_system) {
            unset($validated['slug'], $validated['template']);
        }

        return $validated;
    }

    private function page(): ?CmsPage
    {
        $page = $this->route('page');

        return $page instanceof CmsPage ? $page : null;
    }
}
