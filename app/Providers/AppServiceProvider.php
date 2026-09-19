<?php

namespace App\Providers;

use App\Services\BlockRegistry;
use App\Services\BlockRenderer;
use App\Services\CacheService;
use App\Services\PageBuilderService;
use App\Services\PageRenderContext;
use App\Services\MenuService;
use App\Services\PluginService;
use App\Services\SpotPlayerService;
use App\Services\TaxonomyService;
use App\Services\ThemeService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');

        $this->app->singleton(ThemeService::class);
        $this->app->singleton(PluginService::class);
        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(BlockRenderer::class);
        $this->app->singleton(PageRenderContext::class);
        $this->app->singleton(PageBuilderService::class);
        $this->app->singleton(CacheService::class);
        $this->app->singleton(MenuService::class);
        $this->app->singleton(TaxonomyService::class);
        $this->app->singleton(SpotPlayerService::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (! class_exists('Helper', false)) {
            class_alias(\App\Helpers\Helpers::class, 'Helper');
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        app(ThemeService::class)->registerViews();

        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            app(PluginService::class)->bootActive();
        } catch (\Throwable) {
            // Database may not be migrated yet.
        }
    }
}
