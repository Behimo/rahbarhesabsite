<?php

namespace Plugins\Example;

use App\Support\Hook;
use Illuminate\Support\ServiceProvider;

class ExamplePluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Hook::addAction('order.fulfilled', function ($order) {
            // Custom logic after order fulfillment
        });

        Hook::addFilter('cms.nav.links', function (array $links) {
            return $links;
        });
    }
}
