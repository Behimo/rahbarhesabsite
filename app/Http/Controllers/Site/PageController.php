<?php

namespace App\Http\Controllers\Site;

use Illuminate\View\View;

class PageController extends SiteController
{
    public function show(string $slug): View
    {
        $page = $this->siteData->dynamicPage($slug);

        abort_unless($page, 404);

        return $this->renderSystemPage($slug, 'pages.dynamic', [
            'page' => $page,
            'bodyHtml' => $page->content['body_html'] ?? '',
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => $page->title, 'url' => route('pages.show', $page->slug)],
                ]),
            ],
        ]);
    }

    public function about(): View
    {
        return $this->renderSystemPage('about', 'pages.about', [
            'aboutTimeline' => $this->siteData->aboutTimeline(),
            'aboutPillars' => $this->siteData->aboutPillars(),
            'aboutMission' => $this->siteData->aboutMission(),
            'stats' => $this->siteData->stats(),
            'partners' => $this->siteData->partners(),
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'درباره ما', 'url' => route('about')],
                ]),
            ],
        ]);
    }

    public function contact(): View
    {
        return $this->renderSystemPage('contact', 'pages.contact', [
            'contact' => $this->siteData->contact(),
            'contactFaq' => $this->siteData->contactFaq(),
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'تماس', 'url' => route('contact')],
                ]),
            ],
        ]);
    }
}
