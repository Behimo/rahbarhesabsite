<?php

namespace App\Http\Controllers\Site;

use App\Services\PermalinkService;
use Illuminate\View\View;

class PageController extends SiteController
{
    public function show(string $slug, PermalinkService $permalinks): View
    {
        // Map WordPress page slugs to internal system pages.
        $internal = match ($slug) {
            'about-us' => 'about',
            'contact-us' => 'contact',
            default => $slug,
        };

        if ($internal === 'about') {
            return $this->about($permalinks);
        }

        if ($internal === 'contact') {
            return $this->contact($permalinks);
        }

        $page = $this->siteData->dynamicPage($slug);

        abort_unless($page, 404);

        $content = is_array($page->content) ? $page->content : [];
        $isWpImport = ($page->template === 'wp-html')
            || (($content['import_source'] ?? '') === 'wp-rendered')
            || ! empty($content['stylesheet_urls']);

        return $this->renderSystemPage($slug, $isWpImport ? 'pages.wp-imported' : 'pages.dynamic', [
            'page' => $page,
            'bodyHtml' => $content['body_html'] ?? '',
            'stylesheets' => array_values(array_filter($content['stylesheet_urls'] ?? [])),
            'fullBleedContent' => $isWpImport,
            'seo' => $this->seo->meta([
                'title' => $page->meta_title ?: ($page->title.' | '.config('cms.site_name_fa')),
                'description' => $page->meta_description,
                'keywords' => $page->meta_keywords,
                'og_image' => $page->og_image,
                'canonical' => $page->canonical_url ?: $permalinks->pageAbsolute($page),
                'robots' => $page->robots,
            ]),
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => $page->title, 'url' => $permalinks->pageAbsolute($page)],
                ]),
            ],
        ]);
    }

    public function about(?PermalinkService $permalinks = null): View
    {
        $permalinks ??= app(PermalinkService::class);

        return $this->renderSystemPage('about', 'pages.about', [
            'aboutTimeline' => $this->siteData->aboutTimeline(),
            'aboutPillars' => $this->siteData->aboutPillars(),
            'aboutMission' => $this->siteData->aboutMission(),
            'stats' => $this->siteData->stats(),
            'partners' => $this->siteData->partners(),
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'درباره ما', 'url' => $permalinks->absolute($permalinks->pageBySlug('about'))],
                ]),
            ],
        ]);
    }

    public function contact(?PermalinkService $permalinks = null): View
    {
        $permalinks ??= app(PermalinkService::class);

        return $this->renderSystemPage('contact', 'pages.contact', [
            'contact' => $this->siteData->contact(),
            'contactFaq' => $this->siteData->contactFaq(),
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'تماس', 'url' => $permalinks->absolute($permalinks->pageBySlug('contact'))],
                ]),
            ],
        ]);
    }
}
