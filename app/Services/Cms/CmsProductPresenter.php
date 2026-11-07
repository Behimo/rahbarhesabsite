<?php

namespace App\Services\Cms;

use App\Models\CmsProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CmsProductPresenter
{
    public function __construct(
        private CmsProductRepository $repository,
        private DefaultProductContent $defaults,
    ) {}

    public function defaultProducts(): array
    {
        return $this->defaults->products();
    }

    public function products(): Collection
    {
        return $this->repository->rememberPresented(fn (CmsProduct $product) => $this->formatProduct($product));
    }

    public function product(string $slug): ?array
    {
        $product = $this->repository->findPublished($slug);

        if ($product) {
            return $this->formatProduct($product);
        }

        return null;
    }

    public function productImageUrl(?string $image): ?string
    {
        if (! $image) {
            return null;
        }

        if (str_starts_with($image, 'http') || str_starts_with($image, '/')) {
            return $image;
        }

        return Storage::disk('public')->url($image);
    }

    private function salesFields(): array
    {
        return ['stats', 'capabilities', 'use_cases', 'benefits', 'faqs', 'testimonial'];
    }

    private function mergeSalesContent(array $product, array $defaults): array
    {
        foreach ($this->salesFields() as $field) {
            $product[$field] = $this->storedOrDefault($product[$field] ?? null, $defaults[$field] ?? null);
        }

        return $product;
    }

    private function formatProduct(CmsProduct $product): array
    {
        $defaults = collect($this->defaultProducts())->firstWhere('slug', $product->slug) ?? [];

        return $this->enrichProduct($this->mergeSalesContent([
            'slug' => $product->slug,
            'title' => $product->title,
            'subtitle' => $this->storedOrDefault($product->subtitle, $defaults['subtitle'] ?? null),
            'description' => $this->storedOrDefault($product->description, $defaults['description'] ?? null),
            'accent' => $product->accent,
            'visual' => $product->visual,
            'dashboard_image' => $this->productImageUrl($product->dashboard_image)
                ?? ($defaults['dashboard_image'] ?? null),
            'website_url' => $defaults['website_url'] ?? null,
            'audience' => $this->storedOrDefault($product->audience, $defaults['audience'] ?? null),
            'features' => $this->storedOrDefault($product->features, $defaults['features'] ?? []),
            'cta' => $this->storedOrDefault($product->cta, $defaults['cta'] ?? null),
            'body' => $this->storedOrDefault($product->body, $defaults['body'] ?? null),
            'highlights' => $defaults['highlights'] ?? [],
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
            'og_image' => $product->og_image,
            'is_featured' => $product->is_featured,
        ], $defaults));
    }

    private function storedOrDefault(mixed $stored, mixed $default): mixed
    {
        if (is_string($stored)) {
            return trim($stored) !== '' ? $stored : $default;
        }

        if (is_array($stored)) {
            return $stored !== [] ? $stored : ($default ?? []);
        }

        return $stored ?? $default;
    }

    private function enrichProduct(array $product): array
    {
        $defaults = collect($this->defaultProducts())->firstWhere('slug', $product['slug'] ?? '') ?? [];

        $product['href'] = route('courses.show', $product['slug']);
        $product['dashboard_image'] = $this->productImageUrl($product['dashboard_image'] ?? null)
            ?? ($defaults['dashboard_image'] ?? null);
        $product['website_url'] ??= $defaults['website_url'] ?? null;
        $product['body'] ??= $defaults['body'] ?? null;
        $product['highlights'] ??= $defaults['highlights'] ?? [];

        return $this->mergeSalesContent($product, $defaults);
    }
}
