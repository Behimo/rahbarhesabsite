<?php

namespace App\Providers;

use App\Services\PluginService;
use App\Services\ThemeService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeService::class);
        $this->app->singleton(PluginService::class);
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
