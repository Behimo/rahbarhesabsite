<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(private SeoService $seo) {}

    public function index(): Response
    {
        $sitemaps = $this->seo->sitemapIndex();

        $xml = view('sitemap-index', compact('sitemaps'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function legacy(): Response
    {
        return $this->posts();
    }

    public function posts(): Response
    {
        return $this->renderSitemap($this->seo->postSitemapUrls());
    }

    public function pages(): Response
    {
        return $this->renderSitemap($this->seo->pageSitemapUrls());
    }

    public function courses(): Response
    {
        return $this->renderSitemap($this->seo->courseSitemapUrls());
    }

    public function products(): Response
    {
        return $this->renderSitemap($this->seo->productSitemapUrls());
    }

    public function laws(): Response
    {
        return $this->renderSitemap($this->seo->lawSitemapUrls());
    }

    public function robots(): Response
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $content = app()->environment('production')
            ? "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /panel/\nDisallow: /cart/\nDisallow: /checkout/\nDisallow: /login/\nDisallow: /*?add-to-cart=\n\nSitemap: {$baseUrl}/sitemap_index.xml"
            : "User-agent: *\nDisallow: /";

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function renderSitemap(array $urls): Response
    {
        $permalinks = app(\App\Services\PermalinkService::class);
        $urls = array_map(function (array $item) use ($permalinks): array {
            $path = parse_url($item['loc'], PHP_URL_PATH) ?: '/';
            $item['loc'] = $permalinks->absolute($path);

            return $item;
        }, $urls);
        $xml = view('sitemap', compact('urls'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
