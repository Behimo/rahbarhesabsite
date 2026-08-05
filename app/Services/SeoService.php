<?php

namespace App\Services;

class SeoService
{
    public function __construct(private SiteDataService $siteData) {}

    public function meta(array $overrides = []): array
    {
        $defaults = [
            'title' => config('cms.site_name_fa').' | '.config('cms.site_name'),
            'description' => 'بیسان — ۴ محصول آماده برای فروش و خدمات، یا پروژه اختصاصی. مشاوره رایگان.',
            'keywords' => 'بیسان, BISAN, CRM, راهبر, نوژارو, نرم‌افزار, ایران',
            'og_title' => config('cms.site_name_fa').' — نرم‌افزار درست برای رشد کسب‌وکار شما',
            'og_image' => $this->absoluteUrl(config('cms.default_og_image')),
            'robots' => 'index, follow',
            'canonical' => url()->current(),
        ];

        return array_merge($defaults, array_filter($overrides));
    }

    public function forPage(string $slug, array $extra = []): array
    {
        return $this->meta(array_merge($this->siteData->pageMeta($slug), $extra));
    }

    public function forProduct(array $product): array
    {
        return $this->meta([
            'title' => $product['meta_title'] ?? ($product['title'].' | محصولات بیسان'),
            'description' => $product['meta_description'] ?? $product['description'],
            'keywords' => $product['meta_keywords'] ?? $product['title'].', بیسان, نرم‌افزار',
            'og_title' => $product['meta_title'] ?? $product['title'].' — بیسان',
            'og_image' => isset($product['og_image']) ? $this->absoluteUrl($product['og_image']) : null,
        ]);
    }

    public function organizationSchema(): array
    {
        $contact = $this->siteData->contact();
        $social = array_filter(config('cms.social'));

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('cms.site_name'),
            'alternateName' => config('cms.site_name_fa'),
            'url' => config('app.url'),
            'logo' => $this->absoluteUrl('/images/bisan/logo-full.png'),
            'description' => '۴ محصول آماده برای فروش و خدمات، یا پروژه اختصاصی برای کسب‌وکار شما',
            'email' => $contact['email'],
            'sameAs' => array_values($social),
        ];
    }

    public function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('cms.site_name'),
            'alternateName' => config('cms.site_name_fa'),
            'url' => config('app.url'),
            'inLanguage' => 'fa-IR',
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('cms.site_name'),
            ],
        ];
    }

    public function breadcrumbSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }

    public function webPageSchema(string $name, string $description, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'inLanguage' => 'fa-IR',
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => config('cms.site_name'),
                'url' => config('app.url'),
            ],
        ];
    }

    public function productSchema(array $product): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => $product['title'],
            'alternateName' => $product['subtitle'] ?? null,
            'description' => $product['description'],
            'url' => $product['href'],
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'IRR',
                'description' => 'مشاوره و دمو رایگان',
            ],
            'provider' => [
                '@type' => 'Organization',
                'name' => config('cms.site_name'),
            ],
        ];
    }

    public function sitemapUrls(): array
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => route('products.index'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('blog.index'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('services'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => route('why-bisan'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('about'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('contact'), 'priority' => '0.8', 'changefreq' => 'monthly'],
        ];

        foreach (\App\Models\CmsPost::query()->published()->get() as $post) {
            $urls[] = [
                'loc' => route('blog.show', $post->slug),
                'priority' => '0.7',
                'changefreq' => 'monthly',
                'lastmod' => $post->updated_at?->toAtomString(),
            ];
        }

        foreach (\App\Models\CmsPage::query()->published()->where('is_system', false)->get() as $page) {
            $urls[] = [
                'loc' => route('pages.show', $page->slug),
                'priority' => '0.6',
                'changefreq' => 'monthly',
            ];
        }

        foreach ($this->siteData->products() as $product) {
            $urls[] = [
                'loc' => route('products.show', $product['slug']),
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ];
        }

        return $urls;
    }

    private function absoluteUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return url($path);
    }
}
