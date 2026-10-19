<?php

namespace App\Services;

class SeoService
{
    public function __construct(
        private SiteDataService $siteData,
        private PermalinkService $permalinks,
    ) {}

    public function meta(array $overrides = []): array
    {
        $defaults = [
            'title' => config('cms.site_name_fa').' | '.config('cms.site_name'),
            'description' => 'موسسه آموزش حسابداری راهبر حساب — دوره‌های کاربردی حسابداری و مالیات.',
            'keywords' => 'راهبر حساب, آموزش حسابداری, مالیات, rahbarhesab',
            'og_title' => config('cms.site_name_fa').' — خالق رهبران حسابداری',
            'og_image' => $this->absoluteUrl(config('cms.default_og_image')),
            'robots' => $this->defaultRobots(),
            'canonical' => $this->canonicalUrl(),
        ];
        $meta = array_merge($defaults, array_filter($overrides, fn ($value) => $value !== null && $value !== ''));

        if (! app()->environment('production')) {
            $meta['robots'] = 'noindex, nofollow';
        }

        if (! empty($meta['og_image'])) {
            $meta['og_image'] = $this->absoluteUrl($meta['og_image']);
        }

        return $meta;
    }

    public function forPage(string $slug, array $extra = []): array
    {
        return $this->meta(array_merge($this->siteData->pageMeta($slug), $extra));
    }

    public function forProduct(array $product): array
    {
        return $this->meta([
            'title' => $product['meta_title'] ?? ($product['title'].' | '.config('cms.site_name_fa')),
            'description' => $product['meta_description'] ?? $product['description'],
            'keywords' => $product['meta_keywords'] ?? $product['title'].', راهبر حساب, آموزش حسابداری',
            'og_title' => $product['meta_title'] ?? $product['title'].' — '.config('cms.site_name_fa'),
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
            'logo' => $this->absoluteUrl(config('cms.branding.logo', '/images/rahbarhesab/logo-full.png')),
            'description' => 'موسسه آموزش حسابداری و خدمات مالی راهبر حساب',
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

    public function sitemapIndex(): array
    {
        return [
            ['loc' => $this->absoluteUrl('/post-sitemap.xml')],
            ['loc' => $this->absoluteUrl('/page-sitemap.xml')],
            ['loc' => $this->absoluteUrl('/course-sitemap.xml')],
            ['loc' => $this->absoluteUrl('/law-sitemap.xml')],
        ];
    }

    public function postSitemapUrls(): array
    {
        $urls = [
            ['loc' => $this->permalinks->absolute($this->permalinks->blogIndex()), 'priority' => '0.8', 'changefreq' => 'daily'],
        ];

        foreach (\App\Models\CmsPost::query()->published()->with('categories')->get() as $post) {
            if ($this->isNoindex($post->robots ?? null)) {
                continue;
            }

            $urls[] = [
                'loc' => $this->permalinks->postAbsolute($post),
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $post->updated_at?->toAtomString(),
            ];
        }

        foreach (\App\Models\Category::query()->where('type', \App\Models\Category::TYPE_POST)->where('is_active', true)->get() as $category) {
            $urls[] = [
                'loc' => $this->permalinks->absolute($this->permalinks->category($category)),
                'priority' => '0.6',
                'changefreq' => 'weekly',
                'lastmod' => $category->updated_at?->toAtomString(),
            ];
        }

        foreach (\App\Models\Tag::query()->orderBy('name')->get() as $tag) {
            $urls[] = [
                'loc' => $this->permalinks->absolute($this->permalinks->tag($tag)),
                'priority' => '0.5',
                'changefreq' => 'weekly',
            ];
        }

        return $urls;
    }

    public function pageSitemapUrls(): array
    {
        $urls = [
            ['loc' => $this->permalinks->absolute('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => $this->permalinks->absolute($this->permalinks->productIndex()), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => $this->permalinks->absolute('/cafe-soal'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => $this->permalinks->absolute('/laws'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => $this->permalinks->absolute($this->permalinks->pageBySlug('about')), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => $this->permalinks->absolute($this->permalinks->pageBySlug('contact')), 'priority' => '0.8', 'changefreq' => 'monthly'],
        ];

        foreach (\App\Models\CmsPage::query()->published()->get() as $page) {
            $slug = $page->slug;
            if (in_array($slug, ['home', 'services', 'why-bisan'], true) || $page->is_system) {
                continue;
            }
            if ($this->isNoindex($page->robots ?? null)) {
                continue;
            }

            $urls[] = [
                'loc' => $this->permalinks->pageAbsolute($page),
                'priority' => '0.6',
                'changefreq' => 'monthly',
                'lastmod' => $page->updated_at?->toAtomString(),
            ];
        }

        return $urls;
    }

    public function courseSitemapUrls(): array
    {
        $urls = [];

        foreach (\App\Models\ShopProduct::query()->published()->where('type', 'course')->get() as $course) {
            if ($this->isNoindex($course->robots ?? null)) {
                continue;
            }

            $urls[] = [
                'loc' => $this->permalinks->productAbsolute($course),
                'priority' => '0.9',
                'changefreq' => 'weekly',
                'lastmod' => $course->updated_at?->toAtomString(),
            ];
        }

        foreach (\App\Models\Category::query()->where('type', \App\Models\Category::TYPE_PRODUCT)->where('is_active', true)->get() as $category) {
            $urls[] = [
                'loc' => $this->permalinks->absolute($this->permalinks->category($category)),
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $category->updated_at?->toAtomString(),
            ];
        }

        return $urls;
    }

    public function productSitemapUrls(): array
    {
        // Legacy marketing-product routes redirect to the course listing; do not advertise them.
        return [];
    }

    public function lawSitemapUrls(): array
    {
        $urls = [
            ['loc' => route('laws.index'), 'priority' => '0.8', 'changefreq' => 'weekly'],
        ];

        foreach (\App\Models\AccountingLaw::query()->published()->get() as $law) {
            $urls[] = [
                'loc' => route('laws.show', $law->slug),
                'priority' => '0.7',
                'changefreq' => 'monthly',
                'lastmod' => $law->updated_at?->toAtomString(),
            ];
        }

        return $urls;
    }

    public function courseSchema(\App\Models\ShopProduct $product, ?\App\Models\Course $course = null): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $product->title,
            'description' => $product->description,
            'url' => $this->permalinks->productAbsolute($product),
            'inLanguage' => 'fa-IR',
            'provider' => [
                '@type' => 'Organization',
                'name' => config('cms.site_name_fa'),
                'url' => config('app.url'),
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $product->effectivePrice() * 10,
                'priceCurrency' => 'IRR',
                'availability' => 'https://schema.org/InStock',
                'url' => $this->permalinks->productAbsolute($product),
            ],
            'hasCourseInstance' => array_filter([
                '@type' => 'CourseInstance',
                'courseMode' => 'online',
                'courseWorkload' => 'PT'.($course?->duration_minutes ?: 60).'M',
                'instructor' => $course?->instructor?->name ? [
                    '@type' => 'Person',
                    'name' => $course->instructor->name,
                ] : null,
            ]),
        ];
    }

    public function faqSchema(array $faqs): ?array
    {
        $questions = collect($faqs)
            ->map(fn ($faq) => [
                'question' => trim((string) ($faq['q'] ?? $faq['question'] ?? '')),
                'answer' => trim((string) ($faq['a'] ?? $faq['answer'] ?? '')),
            ])
            ->filter(fn ($faq) => $faq['question'] !== '' && $faq['answer'] !== '')
            ->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ])
            ->values();

        if ($questions->isEmpty()) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions->all(),
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

    private function defaultRobots(): string
    {
        if (request()->routeIs(['search', 'cart.*', 'checkout.*', 'login', 'login.verify', 'panel.*'])) {
            return 'noindex, follow';
        }

        if (request()->hasAny(['s', 'replytocom'])) {
            return 'noindex, follow';
        }

        foreach (array_keys(request()->query()) as $parameter) {
            if (str_starts_with($parameter, 'utm_') || in_array($parameter, ['utm', 'ref', 'add-to-cart'], true)) {
                return 'noindex, follow';
            }
        }

        return 'index, follow';
    }

    private function canonicalUrl(): string
    {
        return $this->permalinks->absolute('/'.trim((string) request()->path(), '/'));
    }

    private function isNoindex(?string $robots): bool
    {
        if (! $robots) {
            return false;
        }

        return str_contains(mb_strtolower($robots), 'noindex');
    }

    private function absoluteUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, '//')) {
            return 'https:'.$path;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return $this->permalinks->absolute($path);
    }
}
