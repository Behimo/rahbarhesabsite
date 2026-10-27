<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\PermalinkService;
use App\Services\SeoService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(private SeoService $seo, private PermalinkService $permalinks) {}

    public function index(): Response
    {
        $sitemaps = $this->seo->sitemapIndex();

        $xml = view('sitemap-index', compact('sitemaps'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function legacy(): Response
    {
        return $this->index();
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
        return $this->renderSitemap($this->seo->courseSitemapUrls());
    }

    public function laws(): Response
    {
        return $this->renderSitemap($this->seo->lawSitemapUrls());
    }

    public function questions(): Response
    {
        return $this->renderSitemap($this->seo->questionSitemapUrls());
    }

    public function robots(): Response
    {
        $baseUrl = $this->permalinks->rootUrl();
        $content = app()->environment('production')
            ? "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /panel/\nDisallow: /cart/\nDisallow: /checkout/\nDisallow: /login/\nDisallow: /*?add-to-cart=\n\nSitemap: {$baseUrl}/sitemap_index.xml"
            : "User-agent: *\nDisallow: /";

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function renderSitemap(array $urls): Response
    {
        $xml = view('sitemap', compact('urls'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
