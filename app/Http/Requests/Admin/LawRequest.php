<?php

namespace App\Http\Requests\Admin;

use App\Models\AccountingLaw;
use App\Models\Category;
use App\Support\PersianSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $title = trim((string) $this->input('title'));
        $slugInput = trim((string) $this->input('slug'));
        $law = $this->route('law');

        if ($slugInput === '' && $title !== '') {
            $slugInput = PersianSlug::unique(
                $title,
                fn (string $slug) => AccountingLaw::withTrashed()
                    ->where('slug', $slug)
                    ->when($law instanceof AccountingLaw, fn ($q) => $q->where('id', '!=', $law->id))
                    ->exists()
            );
        } elseif ($slugInput !== '') {
            $slugInput = PersianSlug::make($slugInput);
        }

        $this->merge(['slug' => $slugInput]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var AccountingLaw|null $law */
        $law = $this->route('law');

        return [
            'article_number' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('accounting_laws', 'slug')
                    ->ignore($law instanceof AccountingLaw ? $law->id : null)
                    ->whereNull('deleted_at'),
            ],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('type', Category::TYPE_LAW)],
            'introduction' => ['nullable', 'string'],
            'article_text' => ['nullable', 'string'],
            'use_cases' => ['nullable', 'string'],
            'plain_language' => ['nullable', 'string'],
            'example' => ['nullable', 'string'],
            'conclusion' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'featured_image_alt' => ['nullable', 'string', 'max:200'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:300'],
            'published_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,published,scheduled'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function lawAttributes(): array
    {
        $validated = $this->validated();

        $checkboxPublished = $this->boolean('is_published');
        $requestedStatus = $validated['status'] ?? AccountingLaw::STATUS_DRAFT;
        $publishedAt = ! empty($validated['published_at']) ? $validated['published_at'] : null;

        $isPublished = $checkboxPublished
            || in_array($requestedStatus, [
                AccountingLaw::STATUS_PUBLISHED,
                AccountingLaw::STATUS_SCHEDULED,
            ], true);

        if ($isPublished && $publishedAt && now()->lt($publishedAt)) {
            $status = AccountingLaw::STATUS_SCHEDULED;
        } elseif ($isPublished) {
            $status = AccountingLaw::STATUS_PUBLISHED;
            $publishedAt = $publishedAt ?: now()->toDateTimeString();
        } else {
            $status = AccountingLaw::STATUS_DRAFT;
        }

        unset($validated['category_id'], $validated['is_published'], $validated['status'], $validated['published_at']);

        $validated['is_published'] = $isPublished;
        $validated['status'] = $status;
        $validated['published_at'] = $publishedAt;

        return $validated;
    }

    public function categoryId(): ?int
    {
        $id = $this->input('category_id');

        return $id ? (int) $id : null;
    }
}
