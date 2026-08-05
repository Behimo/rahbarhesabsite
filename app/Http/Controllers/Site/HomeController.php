<?php

namespace App\Http\Controllers\Site;

use App\Services\HomeContentService;
use App\Services\SeoService;
use App\Services\SiteDataService;
use Illuminate\View\View;

class HomeController extends SiteController
{
    public function __construct(
        SiteDataService $siteData,
        SeoService $seo,
        private HomeContentService $homeContent,
    ) {
        parent::__construct($siteData, $seo);
    }

    public function index(): View
    {
        $home = $this->homeContent->all();
        $seo = $this->seo->forPage('home');

        return $this->render('pages.home', [
            'seo' => $seo,
            'home' => $home,
            'hero' => $home['hero'],
            'heroPills' => $home['hero']['pills'] ?? [],
            'products' => $this->siteData->products()->take(4),
            'stats' => $home['stats'],
            'whyBisan' => $home['why_bisan'],
            'devCapabilities' => $this->siteData->devCapabilities(),
            'devStats' => $this->siteData->devStats(),
            'techStack' => $this->siteData->techStack(),
            'partners' => $home['partners'],
            'testimonials' => $home['testimonials'],
            'trustBadges' => $home['trust_badges'],
            'cta' => $home['cta'],
            'latestPosts' => $this->homeContent->latestPosts(3),
            'structuredData' => [
                $this->seo->organizationSchema(),
                $this->seo->websiteSchema(),
                $this->seo->webPageSchema($seo['title'], $seo['description'], route('home')),
            ],
        ]);
    }
}
