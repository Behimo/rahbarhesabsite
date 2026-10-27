<?php

namespace App\Services;

use App\Models\CmsPost;
use App\Models\Course;
use App\Models\ShopProduct;
use App\Support\Money;
use App\Support\RobotsDirectives;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SeoService
{
    public function __construct(
        private SiteDataService $siteData,
        private PermalinkService $permalinks,
        private Request $request,
        private SitemapService $sitemaps,
    ) {}

    public function meta(array $overrides = []): array
    {
        $defaults = [
            'title' => config('cms.site_name_fa').' | '.config('cms.site_name'),
            'description' => config('seo.defaults.description'),
            'keywords' => config('seo.defaults.keywords'),
            'og_title' => config('cms.site_name_fa').' — خالق رهبران حسابداری',
            'og_image' => $this->absoluteUrl(config('cms.default_og_image')),
            'og_type' => 'website',
            'robots' => $this->defaultRobots(),
            'canonical' => $this->canonicalUrl(),
        ];
        $meta = array_merge($defaults, array_filter($overrides, fn ($value) => $value !== null && $value !== ''));

        $meta['robots'] = RobotsDirectives::restrictive($this->defaultRobots(), $overrides['robots'] ?? null);

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

    public function sitemapIndex(): array
    {
        return [
            ['loc' => $this->absoluteUrl('/post-sitemap.xml')],
            ['loc' => $this->absoluteUrl('/page-sitemap.xml')],
            ['loc' => $this->absoluteUrl('/course-sitemap.xml')],
            ['loc' => $this->absoluteUrl('/law-sitemap.xml')],
            ['loc' => $this->absoluteUrl('/qa-sitemap.xml')],
        ];
    }

    public function postSitemapUrls(): array
    {
        return $this->sitemaps->posts();
    }

    public function pageSitemapUrls(): array
    {
        return $this->sitemaps->pages();
    }

    public function courseSitemapUrls(): array
    {
        return $this->sitemaps->courses();
    }

    public function lawSitemapUrls(): array
    {
        return $this->sitemaps->laws();
    }

    public function questionSitemapUrls(): array
    {
        return $this->sitemaps->questions();
    }

    public function videoSchema(string $name, ?string $description, string $contentUrl, ?string $thumbnailUrl, ?Carbon $uploadDate): array
    {
        if (! $thumbnailUrl) {
            return [];
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $name,
            'description' => $description,
            'contentUrl' => $contentUrl,
            'thumbnailUrl' => $thumbnailUrl,
            'uploadDate' => $uploadDate?->toIso8601String(),
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function courseSchema(ShopProduct $product, ?Course $course = null): array
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
                'price' => Money::tomanToRials($product->effectivePrice()),
                'priceCurrency' => 'IRR',
                'availability' => 'https://schema.org/InStock',
                'url' => $this->permalinks->productAbsolute($product),
            ],
            'hasCourseInstance' => array_filter([
                '@type' => 'CourseInstance',
                'courseMode' => 'online',
                'courseWorkload' => $course?->duration_minutes > 0 ? 'PT'.$course->duration_minutes.'M' : null,
                'instructor' => $course?->instructor?->name ? [
                    '@type' => 'Person',
                    'name' => $course->instructor->name,
                ] : null,
            ]),
        ];
    }

    public function articleSchema(CmsPost $post): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->excerpt ?: null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'mainEntityOfPage' => $post->canonical_url ?: $this->permalinks->postAbsolute($post),
            'image' => $this->absoluteUrl($post->featured_image),
            'author' => ['@type' => 'Person', 'name' => $post->author ?: config('cms.site_name_fa')],
            'publisher' => ['@type' => 'Organization', 'name' => config('cms.site_name')],
        ], fn ($value) => $value !== null && $value !== '');
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

    private function defaultRobots(): string
    {
        if ($this->request->routeIs(['search', 'cart.*', 'checkout.*', 'login', 'login.verify', 'panel.*'])) {
            return 'noindex, follow';
        }

        if ($this->request->hasAny(['s', 'replytocom'])) {
            return 'noindex, follow';
        }

        foreach (array_keys($this->request->query()) as $parameter) {
            if (str_starts_with($parameter, 'utm_') || in_array($parameter, ['utm', 'ref', 'add-to-cart'], true)) {
                return 'noindex, follow';
            }
        }

        return (string) config('seo.defaults.robots', 'index, follow');
    }

    /**
     * Rank Math "%title% %page% %sep% %sitename%" — %page% renders "- صفحه N از M" past page one.
     */
    public function wpTitle(string $title, ?int $page = null, ?int $lastPage = null, ?string $suffix = null): string
    {
        $paged = $page > 1 && $lastPage ? ' - صفحه '.$page.' از '.$lastPage : '';

        return trim($title).$paged.($suffix ?? ' - '.config('cms.site_name_fa'));
    }

    /**
     * Rank Math "%excerpt%": the first real text paragraph (page-builder counters such as
     * "+100 نفر" are skipped), cut at a word boundary.
     */
    public function excerptDescription(?string $html, int $limit = 160): ?string
    {
        $html = (string) $html;
        $plain = fn (string $value) => trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $value)), ENT_QUOTES | ENT_HTML5)));
        $text = '';
        if (preg_match_all('/<p\b[^>]*>(.*?)<\/p>/isu', $html, $matches)) {
            $paragraphs = array_values(array_filter(array_map($plain, $matches[1]), fn ($value) => $value !== ''));
            $text = collect($paragraphs)->first(fn ($value) => count(preg_split('/\s+/u', $value)) >= 5) ?? ($paragraphs[0] ?? '');
        }
        if ($text === '') {
            $text = $plain((string) preg_replace(['/<(h[1-6]|script|style)\b.*?<\/\1>/isu', '/<\/(div|li|h[1-6])>/iu'], [' ', ' '], $html));
        }
        if ($text === '') {
            return null;
        }
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        $cut = mb_substr($text, 0, $limit);
        $space = mb_strrpos($cut, ' ');

        return (string) preg_replace('/[\s،,؛:]+$/u', '', $space ? mb_substr($cut, 0, $space) : $cut);
    }

    private function canonicalUrl(): string
    {
        return $this->permalinks->absolute('/'.trim((string) $this->request->path(), '/'));
    }

    public function absoluteUrl(?string $path): ?string
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
