<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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
    }
}
