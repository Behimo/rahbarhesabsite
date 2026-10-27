<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\CmsPost;
use App\Rules\Robots;
use App\Support\PersianSlug;
use App\Support\RobotsDirectives;
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
        $post = $this->postModel();
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
            $slugInput = trim(preg_replace('/[\s\x{200c}]+/u', '-', mb_strtolower(rawurldecode($slugInput))), '-');
        }

        $this->merge(['slug' => $slugInput]);
        if (is_string($this->input('robots'))) {
            $this->merge(['robots' => RobotsDirectives::normalize($this->input('robots'))]);
        }
    }

    public function rules(): array
    {
        $post = $this->postModel();

        return [
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u',
                Rule::unique('cms_posts', 'slug')
                    ->ignore($post?->id)
                    ->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:200'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('type', Category::TYPE_POST)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'featured_image_alt' => ['nullable', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:100'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:300'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'robots' => ['nullable', 'string', 'max:500', new Robots],
            'published_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,published,scheduled'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
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
            'slug.regex' => 'نامک فقط می‌تواند شامل حروف فارسی یا انگلیسی، عدد و خط تیره باشد.',
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
        $validated['reading_time_minutes'] = $temp->estimateReadingTime();
        $validated['author'] = ($validated['author'] ?? null) ?: config('cms.site_name_fa', 'راهبر حساب');
        $validated['og_image'] = ($validated['og_image'] ?? null) ?: ($validated['featured_image'] ?? null);

        unset($validated['category_id'], $validated['tag_ids']);

        return $validated;
    }

    private function postModel(): ?CmsPost
    {
        $post = $this->route('post');

        return $post instanceof CmsPost ? $post : null;
    }
}
