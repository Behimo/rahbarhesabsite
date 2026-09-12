<?php

namespace App\Providers;

use App\Services\SiteDataService;
use App\Services\ThemeService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $names = app(ThemeService::class)->layoutViewNames();

        View::composer($names, function ($view) {
            $shared = app(SiteDataService::class)->sharedViewData();

            foreach ($shared as $key => $value) {
                $view->with($key, $value);
            }
        });
    }
}
