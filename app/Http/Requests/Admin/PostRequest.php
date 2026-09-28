<?php

namespace App\Http\Requests\Admin;

use App\Models\CmsPost;
use App\Support\PersianSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $post = $this->post();
        $title = trim((string) $this->input('title'));
        $slugInput = trim((string) $this->input('slug'));

        if ($slugInput === '' && $title !== '') {
            $slugInput = PersianSlug::unique(
                $title,
                fn (string $slug) => CmsPost::withTrashed()
                    ->where('slug', $slug)
                    ->when($post, fn ($q) => $q->where('id', '!=', $post->id))
                    ->exists()
            );
        } elseif ($slugInput !== '') {
            $slugInput = PersianSlug::make($slugInput);
        }

        $this->merge(['slug' => $slugInput]);
    }

    public function rules(): array
    {
        $post = $this->post();

        return [
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('cms_posts', 'slug')
                    ->ignore($post?->id)
                    ->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:200'],
            'category_id' => ['nullable', 'exists:cms_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'featured_image_alt' => ['nullable', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:100'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:300'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,published,scheduled'],
            'term_ids' => ['nullable', 'array'],
            'term_ids.*' => ['integer', 'exists:cms_taxonomy_terms,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'نامک',
            'title' => 'عنوان',
            'category_id' => 'دسته‌بندی',
            'excerpt' => 'خلاصه',
            'body' => 'متن',
            'published_at' => 'تاریخ انتشار',
            'status' => 'وضعیت',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'نامک فقط می‌تواند شامل حروف انگلیسی کوچک، عدد و خط تیره باشد.',
            'slug.unique' => 'این نامک قبلاً برای مقاله دیگری ثبت شده است.',
        ];
    }

    public function postAttributes(): array
    {
        $validated = $this->validated();

        $checkboxPublished = $this->boolean('is_published');
        $requestedStatus = $validated['status'] ?? CmsPost::STATUS_DRAFT;
        $publishedAt = ! empty($validated['published_at']) ? $validated['published_at'] : null;

        $isPublished = $checkboxPublished
            || in_array($requestedStatus, [
                CmsPost::STATUS_PUBLISHED,
                CmsPost::STATUS_SCHEDULED,
            ], true);

        if ($isPublished && $publishedAt && now()->lt($publishedAt)) {
            $status = CmsPost::STATUS_SCHEDULED;
        } elseif ($isPublished) {
            $status = CmsPost::STATUS_PUBLISHED;
            $publishedAt = $publishedAt ?: now()->toDateTimeString();
        } else {
            $status = CmsPost::STATUS_DRAFT;
        }

        $temp = new CmsPost(['body' => $validated['body'] ?? '']);

        $validated['is_published'] = $isPublished;
        $validated['status'] = $status;
        $validated['published_at'] = $publishedAt;
        $validated['category_id'] = ($validated['category_id'] ?? null) ?: null;
        $validated['reading_time_minutes'] = $temp->estimateReadingTime();
        $validated['author'] = ($validated['author'] ?? null) ?: config('cms.site_name_fa', 'راهبر حساب');
        $validated['og_image'] = ($validated['og_image'] ?? null) ?: ($validated['featured_image'] ?? null);

        unset($validated['term_ids']);

        return $validated;
    }

    private function post(): ?CmsPost
    {
        $post = $this->route('post');

        return $post instanceof CmsPost ? $post : null;
    }
}
