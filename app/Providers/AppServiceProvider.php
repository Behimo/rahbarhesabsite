<?php

namespace App\Providers;

use App\Helpers\Helpers;
use App\Models\Category;
use App\Models\User;
use App\Observers\CategoryObserver;
use App\Services\BlockRegistry;
use App\Services\BlockRenderer;
use App\Services\CacheService;
use App\Services\CategoryService;
use App\Services\MenuService;
use App\Services\PageBuilderService;
use App\Services\PageRenderContext;
use App\Services\PaymentGatewayRegistry;
use App\Services\SpotPlayerService;
use App\Support\AccessCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');

        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(PaymentGatewayRegistry::class);
        $this->app->singleton(BlockRenderer::class);
        $this->app->singleton(PageRenderContext::class);
        $this->app->singleton(PageBuilderService::class);
        $this->app->singleton(CacheService::class);
        $this->app->singleton(MenuService::class);
        $this->app->singleton(CategoryService::class);
        $this->app->singleton(SpotPlayerService::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Category::observe(CategoryObserver::class);

        Gate::before(function ($user, string $ability) {
            if (! $user instanceof User) {
                return null;
            }

            return $user->hasRole(AccessCatalog::ROLE_ADMIN) ? true : null;
        });

        if (! class_exists('Helper', false)) {
            class_alias(Helpers::class, 'Helper');
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
